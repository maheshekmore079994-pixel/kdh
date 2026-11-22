<?php

namespace Tests\Feature\Operations;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Exceptions\DomainException;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Inventory\ProductShow;
use App\Livewire\Purchases\Form as PurchaseForm;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\PurchaseService;
use App\Services\ReportService;
use App\Services\StockService;
use Carbon\CarbonImmutable;
use Database\Seeders\ConfigurationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpensesAndPurchasesTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ConfigurationSeeder::class);
        $this->actingAs(User::factory()->admin()->create());
        $this->cash = PaymentMethod::query()->where('code', 'cash')->firstOrFail();
    }

    public function test_expense_is_recorded_and_deleted_with_reason(): void
    {
        $category = ExpenseCategory::query()->first();
        $today = CarbonImmutable::today();

        $expense = app(ExpenseService::class)->create([
            'expense_date' => $today->toDateString(), 'expense_category_id' => $category->id, 'description' => 'Electricity bill',
            'amount' => '1250.50', 'payment_method_id' => $this->cash->id,
        ]);
        $this->assertMatchesRegularExpression('/^EXP-\d{4}-00001$/', $expense->expense_number);
        $this->assertSame('1250.50', app(ReportService::class)->expensesTotal($today, $today));

        app(ExpenseService::class)->delete($expense, 'Duplicate entry');
        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
        $this->assertSame('0.00', app(ReportService::class)->expensesTotal($today, $today));
        $this->assertDatabaseHas('audit_logs', ['action' => 'expense.deleted', 'auditable_id' => $expense->id]);
    }

    public function test_expense_component_validates_and_saves(): void
    {
        $category = ExpenseCategory::query()->first();

        Livewire::test(ExpensesIndex::class)
            ->call('create')
            ->set('description', 'Internet')
            ->set('amount', '0')
            ->call('save')
            ->assertHasErrors('amount')
            ->set('amount', '799')
            ->set('categoryId', $category->id)
            ->set('methodId', $this->cash->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Expense::query()->count());
        $this->get(route('expenses.index'))->assertOk()->assertSee('Internet');
    }

    public function test_purchase_posting_adds_stock_and_supplier_payments_track_dues(): void
    {
        $supplier = Supplier::factory()->create();
        $paper = Product::factory()->create(['current_stock' => '0.000', 'purchase_cost' => '200.00']);
        $purchases = app(PurchaseService::class);

        $purchase = $purchases->create(['supplier_id' => $supplier->id, 'other_charges' => '50'], [
            ['product_id' => $paper->id, 'quantity' => 5, 'unit_cost' => '210'],
        ]);

        $this->assertSame(PurchaseStatus::Posted, $purchase->status);
        $this->assertSame('1100.00', $purchase->total);
        $this->assertSame('5.000', $paper->fresh()->current_stock);
        $this->assertSame('210.00', $paper->fresh()->purchase_cost);
        $this->assertSame('1100.00', $supplier->outstandingDue());
        $this->assertSame('1100.00', app(ReportService::class)->supplierDuesTotal());

        $payment = $purchases->recordPayment($purchase, '600', $this->cash->id);
        $purchase->refresh();
        $this->assertSame(PaymentStatus::Partial, $purchase->payment_status);
        $this->assertSame('500.00', $purchase->balance_due);

        // Cancellation blocked while payment active
        try {
            $purchases->cancel($purchase, 'Wrong');
            $this->fail('Expected DomainException');
        } catch (DomainException) {
        }

        $purchases->reversePayment($payment, 'Paid to wrong supplier');
        $purchases->cancel($purchase->fresh(), 'Goods returned');

        $this->assertSame('0.000', $paper->fresh()->current_stock);
        $this->assertSame('0.00', app(ReportService::class)->supplierDuesTotal());
        $this->assertDatabaseHas('stock_movements', ['product_id' => $paper->id, 'type' => StockMovementType::PurchaseReversal->value]);
    }

    public function test_purchase_form_component_posts_with_payment(): void
    {
        $supplier = Supplier::factory()->create();
        $toner = Product::factory()->create();

        Livewire::test(PurchaseForm::class)
            ->set('supplierId', $supplier->id)
            ->call('addProduct', $toner->id)
            ->set('items.0.quantity', '2')
            ->set('items.0.unit_cost', '1500')
            ->set('paidNow', '3000')
            ->set('paymentMethodId', $this->cash->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $purchase = $supplier->purchases()->first();
        $this->assertSame(PaymentStatus::Paid, $purchase->payment_status);
        $this->assertSame('2.000', $toner->fresh()->current_stock);
        $this->get(route('purchases.show', $purchase))->assertOk()->assertSee($purchase->purchase_number);
        $this->get(route('purchases.index'))->assertOk();
        $this->get(route('suppliers.index'))->assertOk();
    }

    public function test_manual_stock_movements_require_reason_and_keep_balance_consistent(): void
    {
        $product = Product::factory()->create(['current_stock' => '0.000']);
        $stock = app(StockService::class);
        $stock->opening($product, '100');
        $this->assertSame('100.000', $product->fresh()->current_stock);

        Livewire::test(ProductShow::class, ['product' => $product->fresh()])
            ->set('movementType', 'wastage')
            ->set('movementQty', '5')
            ->set('movementReason', '')
            ->call('recordMovement')
            ->assertHasErrors('movementReason')
            ->set('movementReason', 'Paper jam damage')
            ->call('recordMovement')
            ->assertHasNoErrors();

        $this->assertSame('95.000', $product->fresh()->current_stock);
        [$stored, $computed] = $stock->verify($product->fresh());
        $this->assertSame($stored, $computed);

        $this->expectException(DomainException::class);
        $stock->manual($product->fresh(), StockMovementType::Consumption, '500', 'Too many');
    }

    public function test_inventory_pages_render(): void
    {
        $product = Product::factory()->create(['current_stock' => '1.000', 'reorder_level' => '5.000']);
        $this->get(route('products.index'))->assertOk()->assertSee($product->name);
        $this->get(route('products.show', $product))->assertOk();
        $this->get(route('stock.index'))->assertOk();
        $this->assertSame(1, app(ReportService::class)->lowStockCount());
    }
}
