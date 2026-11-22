<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\JobStatus;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ServiceJob;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Read-only aggregations used by the dashboard and the reports module.
 * All date ranges are inclusive and interpreted in the app timezone (Asia/Kolkata).
 */
class ReportService
{
    /** Sales = invoiced amounts (non-cancelled). Not cash received. */
    public function salesSummary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $row = Invoice::query()->notCancelled()
            ->whereDate('invoice_date', '>=', $from->toDateString())->whereDate('invoice_date', '<=', $to->toDateString())
            ->selectRaw('COUNT(*) as invoices, COALESCE(SUM(subtotal),0) as subtotal, COALESCE(SUM(discount_amount),0) as discount, COALESCE(SUM(tax_amount),0) as tax, COALESCE(SUM(total),0) as total, COALESCE(SUM(balance_due),0) as balance_due')
            ->first();

        return [
            'invoices' => (int) $row->invoices,
            'subtotal' => Money::of($row->subtotal),
            'discount' => Money::of($row->discount),
            'tax' => Money::of($row->tax),
            'total' => Money::of($row->total),
            'net_of_tax' => Money::sub($row->total, $row->tax),
            'balance_due' => Money::of($row->balance_due),
        ];
    }

    /** Receipts = customer payments actually received, excluding reversed, grouped by method. */
    public function receiptsByMethod(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $rows = Payment::query()->active()
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->join('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->groupBy('payment_methods.id', 'payment_methods.name', 'payment_methods.type')
            ->selectRaw('payment_methods.name as name, payment_methods.type as type, COALESCE(SUM(payments.amount),0) as amount, COUNT(*) as count')
            ->orderBy('payment_methods.sort_order')
            ->get();

        $total = '0.00';
        $byMethod = [];
        foreach ($rows as $row) {
            $byMethod[] = ['name' => $row->name, 'type' => $row->type, 'amount' => Money::of($row->amount), 'count' => (int) $row->count];
            $total = Money::add($total, $row->amount);
        }

        return ['total' => $total, 'methods' => $byMethod];
    }

    public function expensesTotal(CarbonImmutable $from, CarbonImmutable $to): string
    {
        return Money::of(Expense::query()->whereDate('expense_date', '>=', $from->toDateString())->whereDate('expense_date', '<=', $to->toDateString())->sum('amount'));
    }

    public function expensesByMethod(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return Expense::query()
            ->whereDate('expense_date', '>=', $from->toDateString())->whereDate('expense_date', '<=', $to->toDateString())
            ->join('payment_methods', 'payment_methods.id', '=', 'expenses.payment_method_id')
            ->groupBy('payment_methods.name', 'payment_methods.type')
            ->selectRaw('payment_methods.name as name, payment_methods.type as type, COALESCE(SUM(expenses.amount),0) as amount')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'type' => $r->type, 'amount' => Money::of($r->amount)])
            ->all();
    }

    public function supplierPaymentsTotal(CarbonImmutable $from, CarbonImmutable $to): string
    {
        return Money::of(SupplierPayment::query()->active()->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])->sum('amount'));
    }

    public function supplierPaymentsByMethod(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return SupplierPayment::query()->active()
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->join('payment_methods', 'payment_methods.id', '=', 'supplier_payments.payment_method_id')
            ->groupBy('payment_methods.name', 'payment_methods.type')
            ->selectRaw('payment_methods.name as name, payment_methods.type as type, COALESCE(SUM(supplier_payments.amount),0) as amount')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'type' => $r->type, 'amount' => Money::of($r->amount)])
            ->all();
    }

    /** Cost of goods sold: product lines on non-cancelled invoices at snapshot cost. */
    public function costOfGoodsSold(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $sum = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.status', '!=', InvoiceStatus::Cancelled->value)
            ->whereNull('invoices.deleted_at')
            ->whereDate('invoices.invoice_date', '>=', $from->toDateString())->whereDate('invoices.invoice_date', '<=', $to->toDateString())
            ->where('invoice_items.item_type', 'product')
            ->whereNotNull('invoice_items.cost_price')
            ->selectRaw('COALESCE(SUM(invoice_items.quantity * invoice_items.cost_price),0) as cogs')
            ->value('cogs');

        return Money::of($sum);
    }

    /**
     * Estimated profit. See docs/PRODUCT_REQUIREMENTS.md §9 for the definition and exclusions.
     */
    public function profitEstimate(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sales = $this->salesSummary($from, $to);
        $cogs = $this->costOfGoodsSold($from, $to);
        $expenses = $this->expensesTotal($from, $to);

        return [
            'sales_net_of_tax' => $sales['net_of_tax'],
            'cogs' => $cogs,
            'expenses' => $expenses,
            'profit' => Money::sub(Money::sub($sales['net_of_tax'], $cogs), $expenses),
        ];
    }

    public function jobStats(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $base = ServiceJob::query();

        return [
            'received' => (clone $base)->whereBetween('received_at', [$from->startOfDay(), $to->endOfDay()])->count(),
            'pending' => (clone $base)->whereIn('status', [JobStatus::Pending->value, JobStatus::Processing->value])->count(),
            'completed' => (clone $base)->where('status', JobStatus::Completed->value)->count(),
            'delivered' => (clone $base)->where('status', JobStatus::Delivered->value)->whereBetween('delivered_at', [$from->startOfDay(), $to->endOfDay()])->count(),
            'overdue' => (clone $base)->whereIn('status', [JobStatus::Pending->value, JobStatus::Processing->value])->whereNotNull('expected_at')->where('expected_at', '<', now())->count(),
        ];
    }

    public function customerDuesTotal(): string
    {
        return Money::of(Invoice::query()->notCancelled()->where('balance_due', '>', 0)->sum('balance_due'));
    }

    public function customerDues(): Collection
    {
        return Customer::query()
            ->whereHas('invoices', fn ($q) => $q->notCancelled()->where('balance_due', '>', 0))
            ->withSum(['invoices as due' => fn ($q) => $q->notCancelled()], 'balance_due')
            ->orderByDesc('due')
            ->get();
    }

    public function supplierDuesTotal(): string
    {
        return Money::of(Purchase::query()->where('status', 'posted')->where('balance_due', '>', 0)->sum('balance_due'));
    }

    public function supplierDues(): Collection
    {
        return Supplier::query()
            ->whereHas('purchases', fn ($q) => $q->where('status', 'posted')->where('balance_due', '>', 0))
            ->withSum(['purchases as due' => fn ($q) => $q->where('status', 'posted')], 'balance_due')
            ->orderByDesc('due')
            ->get();
    }

    public function lowStockProducts(?int $limit = null): Collection
    {
        $query = Product::query()->lowStock()->orderByRaw('current_stock - reorder_level');

        return $limit ? $query->limit($limit)->get() : $query->get();
    }

    public function lowStockCount(): int
    {
        return Product::query()->lowStock()->count();
    }

    public function inventoryValuation(): array
    {
        $rows = Product::query()->where('is_active', true)->get();
        $total = '0.00';
        $items = $rows->map(function (Product $p) use (&$total) {
            $value = Money::mul($p->current_stock, $p->purchase_cost);
            $total = Money::add($total, $value);

            return ['product' => $p, 'value' => $value];
        });

        return ['items' => $items, 'total' => $total];
    }

    /** Latest invoices, payments and expenses merged by time for the dashboard feed. */
    public function recentActivity(int $limit = 10): Collection
    {
        $invoices = Invoice::query()->with('customer')->latest('id')->limit($limit)->get()->map(fn (Invoice $i) => [
            'type' => 'invoice', 'at' => $i->created_at, 'title' => $i->invoice_number,
            'subtitle' => $i->customer?->name ?? 'Walk-in customer', 'amount' => $i->total, 'status' => $i->status, 'url' => route('invoices.show', $i),
        ]);
        $payments = Payment::query()->with('invoice')->latest('id')->limit($limit)->get()->map(fn (Payment $p) => [
            'type' => 'payment', 'at' => $p->created_at, 'title' => $p->payment_number.' · '.$p->invoice?->invoice_number,
            'subtitle' => $p->isReversed() ? 'Reversed' : 'Payment received', 'amount' => $p->amount, 'status' => null, 'url' => $p->invoice ? route('invoices.show', $p->invoice) : null,
        ]);
        $expenses = Expense::query()->with('category')->latest('id')->limit($limit)->get()->map(fn (Expense $e) => [
            'type' => 'expense', 'at' => $e->created_at, 'title' => $e->description,
            'subtitle' => $e->category?->name, 'amount' => $e->amount, 'status' => null, 'url' => route('expenses.index'),
        ]);

        return $invoices->concat($payments)->concat($expenses)->sortByDesc('at')->take($limit)->values();
    }

    /** Daily cashbook rows: receipts in, expenses + supplier payments out, by payment method type. */
    public function cashbook(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $in = $this->receiptsByMethod($from, $to);
        $outExpenses = $this->expensesByMethod($from, $to);
        $outSupplier = $this->supplierPaymentsByMethod($from, $to);

        $methods = PaymentMethod::query()->orderBy('sort_order')->get();
        $rows = [];
        foreach ($methods as $m) {
            $receipts = collect($in['methods'])->firstWhere('name', $m->name)['amount'] ?? '0.00';
            $exp = collect($outExpenses)->firstWhere('name', $m->name)['amount'] ?? '0.00';
            $sup = collect($outSupplier)->firstWhere('name', $m->name)['amount'] ?? '0.00';
            $rows[] = [
                'method' => $m->name,
                'type' => $m->type->value,
                'receipts' => $receipts,
                'expenses' => $exp,
                'supplier_payments' => $sup,
                'net' => Money::sub(Money::sub($receipts, $exp), $sup),
            ];
        }

        $totals = [
            'receipts' => array_reduce($rows, fn ($c, $r) => Money::add($c, $r['receipts']), '0.00'),
            'expenses' => array_reduce($rows, fn ($c, $r) => Money::add($c, $r['expenses']), '0.00'),
            'supplier_payments' => array_reduce($rows, fn ($c, $r) => Money::add($c, $r['supplier_payments']), '0.00'),
        ];
        $totals['net'] = Money::sub(Money::sub($totals['receipts'], $totals['expenses']), $totals['supplier_payments']);

        return ['rows' => $rows, 'totals' => $totals];
    }

    public static function resolveRange(string $period, ?string $from = null, ?string $to = null): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            'yesterday' => [$today->subDay(), $today->subDay()],
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'month' => [$today->startOfMonth(), $today->endOfMonth()],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'year' => [$today->startOfYear(), $today->endOfYear()],
            'custom' => [
                $from ? CarbonImmutable::parse($from)->startOfDay() : $today,
                $to ? CarbonImmutable::parse($to)->startOfDay() : $today,
            ],
            default => [$today, $today],
        };
    }
}
