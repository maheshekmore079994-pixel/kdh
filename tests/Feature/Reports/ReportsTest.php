<?php

namespace Tests\Feature\Reports;

use App\Enums\Permission;
use App\Livewire\Tools\PrintCalculator;
use App\Livewire\Website\Enquiries;
use App\Models\Customer;
use App\Models\Enquiry;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\PrintCostProfile;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\ReportBuilder;
use Carbon\CarbonImmutable;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $cash;

    private PaymentMethod $upi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfigurationSeeder::class);
        $this->actingAs(User::factory()->admin()->create());
        $this->cash = PaymentMethod::query()->where('code', 'cash')->firstOrFail();
        $this->upi = PaymentMethod::query()->where('code', 'upi')->firstOrFail();
    }

    /** Known fixture: sales 300 (100 unpaid), receipts cash 120 + upi 80, expense 50, COGS 20, cancelled invoice ignored. */
    private function seedFixture(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['current_stock' => '10.000', 'purchase_cost' => '2.00', 'selling_price' => '10.00']);
        $invoices = app(InvoiceService::class);
        $payments = app(PaymentService::class);

        $a = $invoices->create(['customer_id' => $customer->id], [['type' => 'product', 'product_id' => $product->id, 'quantity' => 10, 'unit_price' => '10']]); // 100
        $payments->record($a, '100', $this->cash->id);

        $b = $invoices->create(['customer_id' => $customer->id], [['type' => 'service', 'description' => 'Typing', 'quantity' => 1, 'unit_price' => '200']]); // 200
        $payments->record($b, '20', $this->cash->id);
        $payments->record($b, '80', $this->upi->id); // 100 still due

        $c = $invoices->create(['customer_id' => $customer->id], [['type' => 'service', 'description' => 'Oops', 'quantity' => 1, 'unit_price' => '999']]);
        $invoices->cancel($c, 'Mistake');

        app(ExpenseService::class)->create(['expense_date' => now()->toDateString(), 'expense_category_id' => ExpenseCategory::query()->first()->id, 'description' => 'Tea', 'amount' => '50', 'payment_method_id' => $this->cash->id]);
    }

    public function test_cashbook_and_profit_report_values(): void
    {
        $this->seedFixture();
        $today = CarbonImmutable::today();
        $builder = app(ReportBuilder::class);

        $cashbook = $builder->build('cashbook', $today, $today);
        $this->assertSame('200.00', $cashbook['totals']['receipts']);
        $this->assertSame('50.00', $cashbook['totals']['expenses']);
        $this->assertSame('150.00', $cashbook['totals']['net']);
        $cashRow = collect($cashbook['rows'])->firstWhere('method', 'Cash');
        $upiRow = collect($cashbook['rows'])->firstWhere('method', 'UPI');
        $this->assertSame('120.00', $cashRow['receipts']);
        $this->assertSame('80.00', $upiRow['receipts']);

        $sales = $builder->build('sales', $today, $today);
        $this->assertSame('300.00', $sales['totals']['total']);
        $this->assertSame('100.00', $sales['totals']['balance_due']);
        $this->assertCount(3, $sales['rows']); // cancelled listed but excluded from totals

        $pl = $builder->build('profit-loss', $today, $today);
        $profitRow = collect($pl['rows'])->firstWhere('item', 'Estimated profit');
        $this->assertSame('230.00', $profitRow['amount']); // 300 - 20 cogs - 50 expenses

        $dues = $builder->build('customer-dues', $today, $today);
        $this->assertSame('100.00', $dues['totals']['due']);

        $valuation = $builder->build('inventory-valuation', $today, $today);
        $this->assertSame('0.00', $valuation['totals']['value']); // 10 sold, 0 left
    }

    public function test_supplier_dues_and_purchase_register(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $purchase = app(PurchaseService::class)->create(['supplier_id' => $supplier->id], [['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => '100']]);
        app(PurchaseService::class)->recordPayment($purchase, '100', $this->cash->id);

        $today = CarbonImmutable::today();
        $builder = app(ReportBuilder::class);
        $this->assertSame('200.00', $builder->build('supplier-dues', $today, $today)['totals']['due']);
        $this->assertSame('300.00', $builder->build('purchases', $today, $today)['totals']['total']);
        $this->assertSame('100.00', $builder->build('cashbook', $today, $today)['totals']['supplier_payments']);
    }

    public function test_report_screens_and_exports(): void
    {
        $this->seedFixture();

        $this->get(route('reports.index'))->assertOk();
        foreach (array_keys(ReportBuilder::REPORTS) as $key) {
            $this->get(route('reports.show', $key))->assertOk();
        }

        $this->get(route('reports.export', ['report' => 'sales', 'format' => 'csv', 'period' => 'today']))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->get(route('reports.export', ['report' => 'cashbook', 'format' => 'xlsx', 'period' => 'today']))->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->get(route('reports.export', ['report' => 'profit-loss', 'format' => 'pdf', 'period' => 'today']))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('reports.export', ['report' => 'nope', 'format' => 'csv']))->assertNotFound();

        $operator = User::factory()->operator([Permission::Billing])->create();
        $this->actingAs($operator)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('reports.export', ['report' => 'sales', 'format' => 'csv']))->assertForbidden();
    }

    public function test_print_calculator_estimates_from_profile(): void
    {
        $profile = PrintCostProfile::query()->create(['name' => 'A4 B/W', 'paper_cost_per_sheet' => '0.5000', 'ink_cost_per_page' => '0.3000', 'maintenance_per_page' => '0.1000', 'other_cost_per_page' => null]);

        Livewire::test(PrintCalculator::class)
            ->set('profileId', $profile->id)
            ->set('pages', '10')
            ->set('copies', '2')
            ->set('sides', '2')
            ->set('charge', '40')
            ->assertSee('₹13.00') // 10 sheets × 0.5 = 5 ; 20 pages × 0.4 = 8 → 13.00
            ->assertSee('₹27.00'); // margin

        $this->get(route('tools.calculator'))->assertOk();
    }

    public function test_enquiry_form_and_admin_handling(): void
    {
        auth()->logout();
        $this->post(route('site.enquiry'), ['name' => 'Visitor', 'phone' => '9999988888', 'message' => 'Need PAN card help'])->assertRedirect(route('site.contact'));
        $this->assertDatabaseHas('enquiries', ['phone' => '9999988888', 'status' => 'new']);

        // honeypot filled → silently ignored
        $this->post(route('site.enquiry'), ['name' => 'Bot', 'phone' => '9999977777', 'message' => 'spam', 'website_url' => 'x'])->assertRedirect();
        $this->assertDatabaseMissing('enquiries', ['phone' => '9999977777']);

        $this->post(route('site.enquiry'), ['name' => '', 'phone' => '12', 'message' => ''])->assertSessionHasErrors(['name', 'phone', 'message']);

        $admin = User::factory()->admin()->create();
        $enquiry = Enquiry::query()->first();
        Livewire::actingAs($admin)->test(Enquiries::class)
            ->call('view', $enquiry->id)
            ->set('note', 'Called back')
            ->call('setStatus', 'contacted');
        $this->assertSame('contacted', $enquiry->fresh()->status->value);
        $this->actingAs($admin)->get(route('enquiries.index'))->assertOk();
    }

    public function test_public_pages_render_and_hide_unknown_facts(): void
    {
        auth()->logout();
        $this->get('/')->assertOk()->assertSee('KHADAVLI DIGITAL HUB')->assertSee('8055000414')->assertDontSee('Hours');
        $this->get('/services')->assertOk();
        $this->get('/about')->assertOk();
        $this->get('/contact')->assertOk()->assertSee('Send an enquiry');
        $this->get('/privacy-policy')->assertOk();
        $this->get('/terms')->assertOk();
        $this->get('/sitemap.xml')->assertOk()->assertHeader('content-type', 'application/xml');
    }
}
