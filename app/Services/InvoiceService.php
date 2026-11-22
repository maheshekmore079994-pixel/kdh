<?php

namespace App\Services;

use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Enums\StockMovementType;
use App\Exceptions\DomainException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceJob;
use App\Models\ServiceVariant;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly StockService $stock,
        private readonly SettingsService $settings,
        private readonly AuditService $audit,
    ) {}

    /**
     * Create and issue an invoice.
     *
     * @param  array{customer_id?: int|null, invoice_date?: string|null, discount_amount?: string|float|null, notes?: string|null, service_job_id?: int|null, apply_tax?: bool|null}  $data
     * @param  list<array{type: string, service_id?: int|null, service_variant_id?: int|null, product_id?: int|null, description?: string|null, quantity: string|float, unit?: string|null, unit_price: string|float, discount_amount?: string|float|null}>  $lines
     */
    public function create(array $data, array $lines): Invoice
    {
        if ($lines === []) {
            throw new DomainException(__('Add at least one item to the bill.'));
        }

        return DB::transaction(function () use ($data, $lines) {
            $customer = ! empty($data['customer_id']) ? Customer::query()->findOrFail($data['customer_id']) : null;

            $invoice = new Invoice([
                'invoice_number' => $this->numbering->next('invoice'),
                'customer_id' => $customer?->id,
                'service_job_id' => $data['service_job_id'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'discount_amount' => Money::of($data['discount_amount'] ?? 0),
                'tax_rate' => ($data['apply_tax'] ?? $this->settings->taxEnabled()) && $this->settings->taxEnabled()
                    ? number_format((float) $this->settings->get('tax.rate'), 2, '.', '')
                    : null,
                'notes' => $data['notes'] ?? null,
                'status' => InvoiceStatus::Unpaid,
                'created_by' => Auth::id(),
            ]);
            $invoice->save();

            foreach (array_values($lines) as $i => $line) {
                $item = $this->buildItem($line, $i);
                $invoice->items()->save($item);

                if ($item->item_type === InvoiceItemType::Product && $item->product_id) {
                    $product = Product::query()->findOrFail($item->product_id);
                    $this->stock->record($product, StockMovementType::Sale, '-'.$item->quantity, $product->purchase_cost, $invoice, $invoice->invoice_number);
                }
            }

            $this->recalculate($invoice);

            if ($invoice->service_job_id) {
                ServiceJob::query()->where('id', $invoice->service_job_id)->update(['invoice_id' => $invoice->id]);
            }

            $this->audit->log('invoice.created', $invoice, [], [
                'invoice_number' => $invoice->invoice_number, 'customer_id' => $invoice->customer_id,
                'total' => $invoice->total, 'items' => count($lines),
            ]);

            return $invoice->refresh();
        });
    }

    /**
     * Business rule: a bill without a customer must be settled in full, because dues cannot be
     * attributed to anyone. Call after payments have been recorded (inside the same transaction).
     */
    public function assertWalkInSettled(Invoice $invoice): void
    {
        if (! $invoice->customer_id && Money::cmp($invoice->balance_due, 0) > 0) {
            throw new DomainException(__('Select a customer to leave a balance due. Walk-in bills must be paid in full.'));
        }
    }

    /** Recompute subtotal, tax, total, paid and balance from items and active payments. */
    public function recalculate(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('items');

        $subtotal = '0.00';
        foreach ($invoice->items as $item) {
            $subtotal = Money::add($subtotal, $item->line_total);
        }

        $discount = Money::of($invoice->discount_amount);
        if (Money::cmp($discount, $subtotal) > 0) {
            throw new DomainException(__('Discount cannot exceed the subtotal.'));
        }
        $taxable = Money::sub($subtotal, $discount);
        $tax = $invoice->tax_rate !== null ? Money::percent($taxable, $invoice->tax_rate) : '0.00';
        $total = Money::add($taxable, $tax);

        $paid = Money::of($invoice->payments()->whereNull('reversed_at')->sum('amount'));

        $invoice->subtotal = $subtotal;
        $invoice->tax_amount = $tax;
        $invoice->total = $total;
        $invoice->paid_amount = $paid;
        $invoice->balance_due = Money::max(Money::sub($total, $paid), '0.00');

        if ($invoice->status !== InvoiceStatus::Cancelled) {
            $invoice->status = match (true) {
                Money::cmp($paid, 0) <= 0 && Money::cmp($total, 0) > 0 => InvoiceStatus::Unpaid,
                Money::cmp($paid, $total) >= 0 => InvoiceStatus::Paid,
                default => InvoiceStatus::Partial,
            };
        }

        $invoice->save();

        return $invoice;
    }

    /** Cancel an issued invoice: requires all payments reversed; restores product stock. */
    public function cancel(Invoice $invoice, string $reason): Invoice
    {
        if (trim($reason) === '') {
            throw new DomainException(__('A reason is required to cancel an invoice.'));
        }
        if ($invoice->isCancelled()) {
            throw new DomainException(__('This invoice is already cancelled.'));
        }
        if ($invoice->payments()->whereNull('reversed_at')->exists()) {
            throw new DomainException(__('Reverse all payments on this invoice before cancelling it.'));
        }

        return DB::transaction(function () use ($invoice, $reason) {
            foreach ($invoice->items()->where('item_type', InvoiceItemType::Product->value)->whereNotNull('product_id')->get() as $item) {
                $product = Product::query()->find($item->product_id);
                if ($product) {
                    $this->stock->record($product, StockMovementType::SaleReversal, $item->quantity, $item->cost_price, $invoice, 'Cancelled '.$invoice->invoice_number, true);
                }
            }

            $invoice->status = InvoiceStatus::Cancelled;
            $invoice->cancelled_at = now();
            $invoice->cancelled_by = Auth::id();
            $invoice->cancel_reason = $reason;
            $invoice->balance_due = '0.00';
            $invoice->save();

            if ($invoice->service_job_id) {
                ServiceJob::query()->where('id', $invoice->service_job_id)->where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
            }

            $this->audit->log('invoice.cancelled', $invoice, ['status' => 'issued'], ['status' => 'cancelled', 'total' => $invoice->total], $reason);

            return $invoice;
        });
    }

    private function buildItem(array $line, int $sort): InvoiceItem
    {
        $type = InvoiceItemType::from($line['type'] ?? 'service');
        $quantity = number_format((float) ($line['quantity'] ?? 0), 3, '.', '');
        $unitPrice = Money::of($line['unit_price'] ?? 0);
        $lineDiscount = Money::of($line['discount_amount'] ?? 0);

        if (bccomp($quantity, '0', 3) <= 0) {
            throw new DomainException(__('Quantity must be greater than zero.'));
        }
        if (Money::cmp($unitPrice, 0) < 0) {
            throw new DomainException(__('Price cannot be negative.'));
        }

        $gross = Money::mul($quantity, $unitPrice);
        if (Money::cmp($lineDiscount, $gross) > 0) {
            throw new DomainException(__('Line discount cannot exceed the line amount.'));
        }

        $item = new InvoiceItem([
            'item_type' => $type,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_amount' => $lineDiscount,
            'line_total' => Money::sub($gross, $lineDiscount),
            'unit' => $line['unit'] ?? 'pcs',
            'sort_order' => $sort,
        ]);

        if ($type === InvoiceItemType::Product) {
            $product = Product::query()->findOrFail($line['product_id'] ?? 0);
            if (! $product->is_active || ! $product->is_sellable) {
                throw new DomainException(__(':name is not available for sale.', ['name' => $product->name]));
            }
            $item->product_id = $product->id;
            $item->description = trim((string) ($line['description'] ?? '')) ?: $product->name;
            $item->unit = $line['unit'] ?? $product->unit;
            $item->cost_price = $product->purchase_cost;
        } else {
            $service = ! empty($line['service_id']) ? Service::query()->find($line['service_id']) : null;
            $variant = ! empty($line['service_variant_id']) ? ServiceVariant::query()->find($line['service_variant_id']) : null;
            $item->service_id = $service?->id;
            $item->service_variant_id = $variant?->id;
            $item->description = trim((string) ($line['description'] ?? '')) ?: trim(($service?->name ?? 'Service').($variant ? ' – '.$variant->name : ''));
            $item->unit = $line['unit'] ?? ($service?->unit ?? 'pcs');
        }

        return $item;
    }
}
