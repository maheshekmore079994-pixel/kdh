<?php

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\Permission;
use App\Exceptions\DomainException;
use App\Livewire\Billing\InvoiceShow;
use App\Livewire\Billing\Pos;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceVariant;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfigurationSeeder::class);
        $this->admin = User::factory()->admin()->create();
        $this->cash = PaymentMethod::query()->where('code', 'cash')->firstOrFail();
        $this->actingAs($this->admin);
    }

    public function test_invoice_totals_are_rounded_to_two_decimals(): void
    {
        $service = Service::factory()->priced('2.50')->create();

        $invoice = app(InvoiceService::class)->create(['discount_amount' => '0.50'], [
            ['type' => 'service', 'service_id' => $service->id, 'quantity' => 3, 'unit_price' => '2.50'],
            ['type' => 'service', 'description' => 'Custom', 'quantity' => '1.5', 'unit_price' => '3.333'],
        ]);

        // 3 × 2.50 = 7.50 ; 1.5 × 3.33 = 5.00 (price stored as 3.33) ; subtotal 12.50 ; − 0.50 = 12.00
        $this->assertSame('12.50', $invoice->subtotal);
        $this->assertSame('12.00', $invoice->total);
        $this->assertSame('12.00', $invoice->balance_due);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-00001$/', $invoice->invoice_number);
    }

    public function test_tax_is_applied_only_when_enabled(): void
    {
        $settings = app(SettingsService::class);
        $settings->setMany(['tax.enabled' => true, 'tax.label' => 'GST', 'tax.rate' => '18.00']);

        $invoice = app(InvoiceService::class)->create([], [
            ['type' => 'service', 'description' => 'Print', 'quantity' => 1, 'unit_price' => '100'],
        ]);

        $this->assertSame('18.00', $invoice->tax_rate);
        $this->assertSame('18.00', $invoice->tax_amount);
        $this->assertSame('118.00', $invoice->total);

        $settings->setMany(['tax.enabled' => false]);
        $plain = app(InvoiceService::class)->create([], [
            ['type' => 'service', 'description' => 'Print', 'quantity' => 1, 'unit_price' => '100'],
        ]);
        $this->assertNull($plain->tax_rate);
        $this->assertSame('100.00', $plain->total);
    }

    public function test_walk_in_bill_cannot_leave_balance_due(): void
    {
        $service = Service::factory()->priced('50.00')->create();

        Livewire::test(Pos::class)
            ->call('addService', $service->id)
            ->set('paymentAmount', '20')
            ->set('paymentMethodId', $this->cash->id)
            ->call('save')
            ->assertNoRedirect();

        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(0, Payment::query()->count());

        // Paid in full → allowed without a customer
        Livewire::test(Pos::class)
            ->call('addService', $service->id)
            ->call('payFull')
            ->set('paymentMethodId', $this->cash->id)
            ->call('save')
            ->assertRedirect();

        $this->assertSame(InvoiceStatus::Paid, Invoice::query()->first()->status);
    }

    public function test_partial_payments_update_status_and_balance(): void
    {
        $customer = Customer::factory()->create();
        $invoice = app(InvoiceService::class)->create(['customer_id' => $customer->id], [
            ['type' => 'service', 'description' => 'Typing', 'quantity' => 1, 'unit_price' => '300'],
        ]);

        $payments = app(PaymentService::class);
        $payments->record($invoice, '100', $this->cash->id);
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Partial, $invoice->status);
        $this->assertSame('200.00', $invoice->balance_due);
        $this->assertSame('200.00', $customer->outstandingDue());

        $payments->record($invoice, '200', $this->cash->id);
        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame('0.00', $invoice->balance_due);

        $this->expectException(DomainException::class);
        $payments->record($invoice, '1', $this->cash->id);
    }

    public function test_reversed_payment_restores_balance_and_is_excluded_from_receipts(): void
    {
        $customer = Customer::factory()->create();
        $invoice = app(InvoiceService::class)->create(['customer_id' => $customer->id], [
            ['type' => 'service', 'description' => 'Typing', 'quantity' => 1, 'unit_price' => '300'],
        ]);
        $payment = app(PaymentService::class)->record($invoice, '300', $this->cash->id);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);

        app(PaymentService::class)->reverse($payment, 'Entered by mistake');

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertSame('300.00', $invoice->balance_due);

        $today = CarbonImmutable::today();
        $receipts = app(ReportService::class)->receiptsByMethod($today, $today);
        $this->assertSame('0.00', $receipts['total']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.reversed', 'auditable_id' => $payment->id]);
    }

    public function test_product_lines_consume_stock_and_cancellation_restores_it(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000', 'purchase_cost' => '2.00', 'selling_price' => '5.00']);

        $invoice = app(InvoiceService::class)->create([], [
            ['type' => 'product', 'product_id' => $product->id, 'quantity' => 4, 'unit_price' => '5.00'],
        ]);
        app(PaymentService::class)->record($invoice, '20', $this->cash->id);

        $this->assertSame('6.000', $product->fresh()->current_stock);
        $this->assertSame('2.00', $invoice->items->first()->cost_price);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sale', 'quantity' => '-4.000']);

        $today = CarbonImmutable::today();
        $reports = app(ReportService::class);
        $this->assertSame('20.00', $reports->salesSummary($today, $today)['total']);
        $this->assertSame('8.00', $reports->costOfGoodsSold($today, $today));
        $this->assertSame('12.00', $reports->profitEstimate($today, $today)['profit']);

        // Cannot cancel while a payment is active
        try {
            app(InvoiceService::class)->cancel($invoice, 'Wrong bill');
            $this->fail('Expected DomainException');
        } catch (DomainException) {
        }

        app(PaymentService::class)->reverse($invoice->payments()->first(), 'Refund');
        app(InvoiceService::class)->cancel($invoice->fresh(), 'Wrong bill');

        $this->assertSame('10.000', $product->fresh()->current_stock);
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'sale_reversal', 'quantity' => '4.000']);
        $this->assertSame('0.00', $reports->salesSummary($today, $today)['total']);
        $this->assertSame('0.00', $reports->costOfGoodsSold($today, $today));
        $this->assertSame('0.00', $reports->customerDuesTotal());
    }

    public function test_insufficient_stock_blocks_the_sale(): void
    {
        $product = Product::factory()->create(['current_stock' => '1.000']);

        $this->expectException(DomainException::class);
        app(InvoiceService::class)->create([], [
            ['type' => 'product', 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => '5.00'],
        ]);
    }

    public function test_pos_component_creates_invoice_with_payment(): void
    {
        $service = Service::factory()->create(['default_price' => null]);
        $variant = ServiceVariant::factory()->create(['service_id' => $service->id, 'unit_price' => '2.00', 'min_quantity' => '1']);
        $customer = Customer::factory()->create();

        Livewire::test(Pos::class)
            ->set('customerId', $customer->id)
            ->call('addService', $service->id, $variant->id)
            ->set('lines.0.quantity', '10')
            ->set('paymentAmount', '15')
            ->set('paymentMethodId', $this->cash->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $invoice = $customer->invoices()->first();
        $this->assertSame('20.00', $invoice->total);
        $this->assertSame('5.00', $invoice->balance_due);
        $this->assertSame(InvoiceStatus::Partial, $invoice->status);
        $this->assertSame($variant->id, $invoice->items->first()->service_variant_id);
    }

    public function test_pos_requires_price_for_every_line(): void
    {
        $service = Service::factory()->create(['default_price' => null]);

        Livewire::test(Pos::class)
            ->call('addService', $service->id)
            ->call('save')
            ->assertHasErrors('lines.0.unit_price');
    }

    public function test_invoice_show_records_and_reverses_payment(): void
    {
        $customer = Customer::factory()->create();
        $invoice = app(InvoiceService::class)->create(['customer_id' => $customer->id], [
            ['type' => 'service', 'description' => 'Scan', 'quantity' => 2, 'unit_price' => '10'],
        ]);

        $component = Livewire::test(InvoiceShow::class, ['invoice' => $invoice])
            ->set('payAmount', '25')
            ->set('payMethodId', $this->cash->id)
            ->call('recordPayment')
            ->assertHasErrors('payAmount') // exceeds balance
            ->set('payAmount', '20')
            ->call('recordPayment')
            ->assertHasNoErrors();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);

        $payment = $invoice->payments()->first();
        $component->call('startReverse', $payment->id)->set('reverseReason', 'Customer returned')->call('reversePayment')->assertHasNoErrors();
        $this->assertNotNull($payment->fresh()->reversed_at);

        $component->set('cancelReason', 'Duplicate bill')->call('cancelInvoice')->assertHasNoErrors();
        $this->assertSame(InvoiceStatus::Cancelled, $invoice->fresh()->status);
    }

    public function test_operator_without_billing_permission_is_forbidden(): void
    {
        $operator = User::factory()->operator([Permission::Customers])->create();

        $this->actingAs($operator)->get(route('pos'))->assertForbidden();
        $this->actingAs($operator)->get(route('invoices.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('customers.index'))->assertOk();
    }

    public function test_print_and_pdf_views_render(): void
    {
        $customer = Customer::factory()->create();
        $invoice = app(InvoiceService::class)->create(['customer_id' => $customer->id], [
            ['type' => 'service', 'description' => 'Lamination', 'quantity' => 1, 'unit_price' => '40'],
        ]);
        $payment = app(PaymentService::class)->record($invoice, '40', $this->cash->id);

        $this->get(route('print.invoice', [$invoice, 'receipt']))->assertOk()->assertSee($invoice->invoice_number)->assertSee('Lamination');
        $this->get(route('print.invoice', [$invoice, 'a4']))->assertOk()->assertSee('INVOICE');
        $this->get(route('print.payment', $payment))->assertOk()->assertSee($payment->payment_number);
        $this->get(route('pdf.invoice', [$invoice, 'a4']))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSee($invoice->invoice_number);
        $this->get(route('invoices.index'))->assertOk();
        $this->get(route('payments.index'))->assertOk();
        $this->get(route('customers.show', $customer))->assertOk();
    }
}
