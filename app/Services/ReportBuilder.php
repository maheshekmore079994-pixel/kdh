<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Purchase;
use App\Support\Money;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Builds a uniform tabular structure for every report so screens and exports share one source.
 *
 * Structure: ['key', 'title', 'description', 'ranged' => bool, 'columns' => [[key,label,type,align]],
 *             'rows' => list<array>, 'totals' => array|null, 'summary' => list<[label, value]>]
 */
class ReportBuilder
{
    public const REPORTS = [
        'cashbook' => ['title' => 'Daily cashbook', 'description' => 'Money in and out by payment method. Receipts are actual customer payments; unpaid bills are not included.', 'ranged' => true],
        'sales' => ['title' => 'Sales register', 'description' => 'All invoices issued in the period, including cancelled ones (marked, excluded from totals).', 'ranged' => true],
        'payments' => ['title' => 'Payment register', 'description' => 'Customer payments received, with reversals shown but excluded from totals.', 'ranged' => true],
        'expenses' => ['title' => 'Expense register', 'description' => 'Shop expenses by date and category.', 'ranged' => true],
        'purchases' => ['title' => 'Purchase register', 'description' => 'Supplier purchases, payments and balances.', 'ranged' => true],
        'customer-dues' => ['title' => 'Customer dues', 'description' => 'Outstanding balances on unpaid and partly paid invoices (as of now).', 'ranged' => false],
        'supplier-dues' => ['title' => 'Supplier dues', 'description' => 'Amounts still owed to suppliers (as of now).', 'ranged' => false],
        'inventory-valuation' => ['title' => 'Inventory valuation', 'description' => 'Current stock valued at purchase cost (as of now).', 'ranged' => false],
        'profit-loss' => ['title' => 'Profit & loss (estimate)', 'description' => 'Sales net of tax minus product cost and expenses. Service consumable costs are not tracked, so this is an estimate.', 'ranged' => true],
    ];

    public function __construct(private readonly ReportService $reports) {}

    public static function exists(string $key): bool
    {
        return isset(self::REPORTS[$key]);
    }

    public function build(string $key, CarbonImmutable $from, CarbonImmutable $to): array
    {
        if (! self::exists($key)) {
            throw new InvalidArgumentException("Unknown report [$key]");
        }

        $meta = self::REPORTS[$key] + ['key' => $key, 'from' => $from, 'to' => $to];

        return $meta + match ($key) {
            'cashbook' => $this->cashbook($from, $to),
            'sales' => $this->sales($from, $to),
            'payments' => $this->payments($from, $to),
            'expenses' => $this->expenses($from, $to),
            'purchases' => $this->purchases($from, $to),
            'customer-dues' => $this->customerDues(),
            'supplier-dues' => $this->supplierDues(),
            'inventory-valuation' => $this->inventoryValuation(),
            'profit-loss' => $this->profitLoss($from, $to),
        };
    }

    private function col(string $key, string $label, string $type = 'text'): array
    {
        return ['key' => $key, 'label' => $label, 'type' => $type, 'align' => in_array($type, ['money', 'qty', 'int'], true) ? 'end' : 'start'];
    }

    private function cashbook(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $book = $this->reports->cashbook($from, $to);
        $sales = $this->reports->salesSummary($from, $to);

        return [
            'columns' => [$this->col('method', 'Payment method'), $this->col('receipts', 'Receipts (in)', 'money'), $this->col('expenses', 'Expenses (out)', 'money'), $this->col('supplier_payments', 'Supplier payments (out)', 'money'), $this->col('net', 'Net', 'money')],
            'rows' => $book['rows'],
            'totals' => ['method' => 'Total'] + $book['totals'],
            'summary' => [
                ['Invoiced sales in period (not cash)', money($sales['total'])],
                ['Of which still unpaid', money($sales['balance_due'])],
                ['Total receipts', money($book['totals']['receipts'])],
                ['Net cash movement', money($book['totals']['net'])],
            ],
        ];
    }

    private function sales(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $invoices = Invoice::query()->with('customer')->between($from->toDateString(), $to->toDateString())->orderBy('invoice_date')->orderBy('id')->get();
        $totals = ['invoice_number' => 'Total (excl. cancelled)', 'subtotal' => '0.00', 'discount_amount' => '0.00', 'tax_amount' => '0.00', 'total' => '0.00', 'paid_amount' => '0.00', 'balance_due' => '0.00'];
        $rows = $invoices->map(function (Invoice $i) use (&$totals) {
            if (! $i->isCancelled()) {
                foreach (['subtotal', 'discount_amount', 'tax_amount', 'total', 'paid_amount', 'balance_due'] as $k) {
                    $totals[$k] = Money::add($totals[$k], $i->$k);
                }
            }

            return [
                'invoice_date' => $i->invoice_date->format('d M Y'), 'invoice_number' => $i->invoice_number, 'customer' => $i->customer?->name ?? 'Walk-in',
                'subtotal' => $i->subtotal, 'discount_amount' => $i->discount_amount, 'tax_amount' => $i->tax_amount, 'total' => $i->total,
                'paid_amount' => $i->paid_amount, 'balance_due' => $i->balance_due, 'status' => $i->status->label(),
            ];
        })->all();

        return [
            'columns' => [$this->col('invoice_date', 'Date'), $this->col('invoice_number', 'Invoice'), $this->col('customer', 'Customer'), $this->col('subtotal', 'Subtotal', 'money'), $this->col('discount_amount', 'Discount', 'money'), $this->col('tax_amount', 'Tax', 'money'), $this->col('total', 'Total', 'money'), $this->col('paid_amount', 'Paid', 'money'), $this->col('balance_due', 'Due', 'money'), $this->col('status', 'Status')],
            'rows' => $rows,
            'totals' => $totals,
            'summary' => [['Invoices', (string) $invoices->where('status', '!=', InvoiceStatus::Cancelled)->count()], ['Cancelled', (string) $invoices->where('status', InvoiceStatus::Cancelled)->count()]],
        ];
    }

    private function payments(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $payments = Payment::query()->with(['invoice.customer', 'method', 'receiver'])->between($from->startOfDay(), $to->endOfDay())->orderBy('paid_at')->get();
        $total = '0.00';
        $rows = $payments->map(function (Payment $p) use (&$total) {
            if (! $p->isReversed()) {
                $total = Money::add($total, $p->amount);
            }

            return [
                'paid_at' => $p->paid_at->format('d M Y h:i A'), 'payment_number' => $p->payment_number, 'invoice' => $p->invoice?->invoice_number,
                'customer' => $p->invoice?->customer?->name ?? 'Walk-in', 'method' => $p->method?->name, 'reference' => $p->reference,
                'received_by' => $p->receiver?->name, 'amount' => $p->amount, 'status' => $p->isReversed() ? 'Reversed' : 'OK',
            ];
        })->all();
        $byMethod = $this->reports->receiptsByMethod($from, $to);

        return [
            'columns' => [$this->col('paid_at', 'When'), $this->col('payment_number', 'Receipt'), $this->col('invoice', 'Invoice'), $this->col('customer', 'Customer'), $this->col('method', 'Method'), $this->col('reference', 'Reference'), $this->col('received_by', 'Received by'), $this->col('amount', 'Amount', 'money'), $this->col('status', 'Status')],
            'rows' => $rows,
            'totals' => ['paid_at' => 'Total (excl. reversed)', 'amount' => $total],
            'summary' => array_map(fn ($m) => [$m['name'], money($m['amount'])], $byMethod['methods']),
        ];
    }

    private function expenses(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $expenses = Expense::query()->with(['category', 'method', 'creator'])->between($from->toDateString(), $to->toDateString())->orderBy('expense_date')->orderBy('id')->get();
        $total = '0.00';
        $byCategory = [];
        $rows = $expenses->map(function (Expense $e) use (&$total, &$byCategory) {
            $total = Money::add($total, $e->amount);
            $byCategory[$e->category?->name ?? '—'] = Money::add($byCategory[$e->category?->name ?? '—'] ?? '0.00', $e->amount);

            return [
                'expense_date' => $e->expense_date->format('d M Y'), 'expense_number' => $e->expense_number, 'category' => $e->category?->name,
                'description' => $e->description, 'vendor' => $e->vendor, 'method' => $e->method?->name, 'amount' => $e->amount, 'by' => $e->creator?->name,
            ];
        })->all();
        arsort($byCategory);

        return [
            'columns' => [$this->col('expense_date', 'Date'), $this->col('expense_number', 'No.'), $this->col('category', 'Category'), $this->col('description', 'Description'), $this->col('vendor', 'Vendor'), $this->col('method', 'Paid via'), $this->col('amount', 'Amount', 'money'), $this->col('by', 'By')],
            'rows' => $rows,
            'totals' => ['expense_date' => 'Total', 'amount' => $total],
            'summary' => array_map(fn ($k, $v) => [$k, money($v)], array_keys($byCategory), $byCategory),
        ];
    }

    private function purchases(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $purchases = Purchase::query()->with('supplier')->whereDate('purchase_date', '>=', $from->toDateString())->whereDate('purchase_date', '<=', $to->toDateString())->orderBy('purchase_date')->get();
        $totals = ['purchase_number' => 'Total (excl. cancelled)', 'total' => '0.00', 'paid_amount' => '0.00', 'balance_due' => '0.00'];
        $rows = $purchases->map(function (Purchase $p) use (&$totals) {
            if ($p->status->value !== 'cancelled') {
                foreach (['total', 'paid_amount', 'balance_due'] as $k) {
                    $totals[$k] = Money::add($totals[$k], $p->$k);
                }
            }

            return [
                'purchase_date' => $p->purchase_date->format('d M Y'), 'purchase_number' => $p->purchase_number, 'supplier' => $p->supplier?->name,
                'supplier_invoice_no' => $p->supplier_invoice_no, 'total' => $p->total, 'paid_amount' => $p->paid_amount, 'balance_due' => $p->balance_due,
                'status' => $p->status->label().' / '.$p->payment_status->label(),
            ];
        })->all();

        return [
            'columns' => [$this->col('purchase_date', 'Date'), $this->col('purchase_number', 'Purchase'), $this->col('supplier', 'Supplier'), $this->col('supplier_invoice_no', 'Supplier bill'), $this->col('total', 'Total', 'money'), $this->col('paid_amount', 'Paid', 'money'), $this->col('balance_due', 'Due', 'money'), $this->col('status', 'Status')],
            'rows' => $rows,
            'totals' => $totals,
            'summary' => [['Supplier payments in period', money($this->reports->supplierPaymentsTotal($from, $to))]],
        ];
    }

    private function customerDues(): array
    {
        $dues = $this->reports->customerDues();
        $total = '0.00';
        $rows = $dues->map(function ($c) use (&$total) {
            $total = Money::add($total, $c->due);
            $oldest = $c->invoices()->notCancelled()->where('balance_due', '>', 0)->orderBy('invoice_date')->first();

            return ['customer' => $c->name, 'mobile' => $c->mobile, 'invoices' => (string) $c->invoices()->notCancelled()->where('balance_due', '>', 0)->count(), 'oldest' => $oldest?->invoice_date->format('d M Y'), 'due' => Money::of($c->due)];
        })->all();

        return [
            'columns' => [$this->col('customer', 'Customer'), $this->col('mobile', 'Mobile'), $this->col('invoices', 'Open invoices', 'int'), $this->col('oldest', 'Oldest due'), $this->col('due', 'Amount due', 'money')],
            'rows' => $rows,
            'totals' => ['customer' => 'Total', 'due' => $total],
            'summary' => [['Customers with dues', (string) count($rows)]],
        ];
    }

    private function supplierDues(): array
    {
        $dues = $this->reports->supplierDues();
        $total = '0.00';
        $rows = $dues->map(function ($s) use (&$total) {
            $total = Money::add($total, $s->due);

            return ['supplier' => $s->name, 'phone' => $s->phone, 'purchases' => (string) $s->purchases()->where('status', 'posted')->where('balance_due', '>', 0)->count(), 'due' => Money::of($s->due)];
        })->all();

        return [
            'columns' => [$this->col('supplier', 'Supplier'), $this->col('phone', 'Phone'), $this->col('purchases', 'Open purchases', 'int'), $this->col('due', 'Amount due', 'money')],
            'rows' => $rows,
            'totals' => ['supplier' => 'Total', 'due' => $total],
            'summary' => [],
        ];
    }

    private function inventoryValuation(): array
    {
        $val = $this->reports->inventoryValuation();
        $rows = $val['items']->map(fn ($r) => [
            'sku' => $r['product']->sku, 'name' => $r['product']->name, 'category' => $r['product']->category?->name,
            'stock' => qty($r['product']->current_stock).' '.$r['product']->unit, 'cost' => $r['product']->purchase_cost, 'value' => $r['value'],
            'low' => $r['product']->isLowStock() ? 'LOW' : '',
        ])->all();

        return [
            'columns' => [$this->col('sku', 'SKU'), $this->col('name', 'Product'), $this->col('category', 'Category'), $this->col('stock', 'In stock', 'qty'), $this->col('cost', 'Unit cost', 'money'), $this->col('value', 'Value', 'money'), $this->col('low', 'Alert')],
            'rows' => $rows,
            'totals' => ['sku' => 'Total', 'value' => $val['total']],
            'summary' => [['Low-stock products', (string) $this->reports->lowStockCount()]],
        ];
    }

    private function profitLoss(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sales = $this->reports->salesSummary($from, $to);
        $pl = $this->reports->profitEstimate($from, $to);
        $receipts = $this->reports->receiptsByMethod($from, $to);
        $expensesByCat = collect($this->expenses($from, $to)['summary']);

        $rows = [
            ['item' => 'Sales (invoiced, excl. cancelled)', 'amount' => $sales['total'], 'note' => $sales['invoices'].' invoices'],
            ['item' => 'Less: tax collected', 'amount' => '-'.$sales['tax'], 'note' => ''],
            ['item' => 'Net sales', 'amount' => $pl['sales_net_of_tax'], 'note' => ''],
            ['item' => 'Less: cost of products sold', 'amount' => '-'.$pl['cogs'], 'note' => 'Stock items at purchase cost'],
            ['item' => 'Less: expenses', 'amount' => '-'.$pl['expenses'], 'note' => $expensesByCat->map(fn ($r) => $r[0].' '.$r[1])->join('; ')],
            ['item' => 'Estimated profit', 'amount' => $pl['profit'], 'note' => 'Excludes service consumables, supplier dues, drawings, depreciation'],
        ];

        return [
            'columns' => [$this->col('item', 'Item'), $this->col('amount', 'Amount', 'money'), $this->col('note', 'Notes')],
            'rows' => $rows,
            'totals' => null,
            'summary' => [['Cash actually received in period', money($receipts['total'])], ['Sales still unpaid', money($sales['balance_due'])]],
        ];
    }
}
