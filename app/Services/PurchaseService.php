<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Exceptions\DomainException;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\SupplierPayment;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly StockService $stock,
        private readonly AuditService $audit,
    ) {}

    /**
     * Create a purchase and (by default) post it immediately, which adds stock.
     *
     * @param  list<array{product_id: int, quantity: string|float, unit_cost: string|float}>  $items
     */
    public function create(array $data, array $items, bool $post = true): Purchase
    {
        if ($items === []) {
            throw new DomainException(__('Add at least one item to the purchase.'));
        }

        return DB::transaction(function () use ($data, $items, $post) {
            $purchase = Purchase::query()->create([
                'purchase_number' => $this->numbering->next('purchase'),
                'supplier_id' => $data['supplier_id'],
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
                'other_charges' => Money::of($data['other_charges'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'status' => PurchaseStatus::Draft,
                'payment_status' => PaymentStatus::Unpaid,
                'created_by' => Auth::id(),
            ]);

            foreach ($items as $line) {
                $product = Product::query()->findOrFail($line['product_id']);
                $qty = number_format((float) $line['quantity'], 3, '.', '');
                if (bccomp($qty, '0', 3) <= 0) {
                    throw new DomainException(__('Quantity must be greater than zero.'));
                }
                $cost = Money::of($line['unit_cost']);
                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'line_total' => Money::mul($qty, $cost),
                ]);
            }

            $this->recalculate($purchase);
            $this->audit->log('purchase.created', $purchase, [], ['purchase_number' => $purchase->purchase_number, 'total' => $purchase->total]);

            return $post ? $this->post($purchase) : $purchase;
        });
    }

    /** Post a draft purchase: adds stock and updates product purchase cost to the latest cost. */
    public function post(Purchase $purchase): Purchase
    {
        if ($purchase->status !== PurchaseStatus::Draft) {
            throw new DomainException(__('Only draft purchases can be posted.'));
        }

        return DB::transaction(function () use ($purchase) {
            foreach ($purchase->items()->with('product')->get() as $item) {
                $this->stock->record($item->product, StockMovementType::Purchase, $item->quantity, $item->unit_cost, $purchase, $purchase->purchase_number);
                $item->product->purchase_cost = $item->unit_cost;
                $item->product->save();
            }
            $purchase->status = PurchaseStatus::Posted;
            $purchase->posted_at = now();
            $purchase->save();

            $this->audit->log('purchase.posted', $purchase, ['status' => 'draft'], ['status' => 'posted', 'total' => $purchase->total]);

            return $purchase;
        });
    }

    public function cancel(Purchase $purchase, string $reason): Purchase
    {
        if (trim($reason) === '') {
            throw new DomainException(__('A reason is required to cancel a purchase.'));
        }
        if ($purchase->status === PurchaseStatus::Cancelled) {
            throw new DomainException(__('This purchase is already cancelled.'));
        }
        if ($purchase->payments()->whereNull('reversed_at')->exists()) {
            throw new DomainException(__('Reverse all supplier payments on this purchase before cancelling it.'));
        }

        return DB::transaction(function () use ($purchase, $reason) {
            if ($purchase->status === PurchaseStatus::Posted) {
                foreach ($purchase->items()->with('product')->get() as $item) {
                    $this->stock->record($item->product, StockMovementType::PurchaseReversal, '-'.$item->quantity, $item->unit_cost, $purchase, 'Cancelled '.$purchase->purchase_number, true);
                }
            }
            $purchase->status = PurchaseStatus::Cancelled;
            $purchase->cancelled_at = now();
            $purchase->cancelled_by = Auth::id();
            $purchase->cancel_reason = $reason;
            $purchase->balance_due = '0.00';
            $purchase->save();

            $this->audit->log('purchase.cancelled', $purchase, [], ['total' => $purchase->total], $reason);

            return $purchase;
        });
    }

    public function recordPayment(Purchase $purchase, string|float $amount, int $paymentMethodId, ?string $paidAt = null, ?string $reference = null, ?string $notes = null): SupplierPayment
    {
        $amount = Money::of($amount);
        if (Money::cmp($amount, 0) <= 0) {
            throw new DomainException(__('Payment amount must be greater than zero.'));
        }
        if ($purchase->status === PurchaseStatus::Cancelled) {
            throw new DomainException(__('Payments cannot be recorded on a cancelled purchase.'));
        }
        if (Money::cmp($amount, $purchase->balance_due) > 0) {
            throw new DomainException(__('Amount exceeds the balance due (:due).', ['due' => money($purchase->balance_due)]));
        }
        $method = PaymentMethod::query()->where('is_active', true)->findOrFail($paymentMethodId);

        return DB::transaction(function () use ($purchase, $amount, $method, $paidAt, $reference, $notes) {
            $payment = SupplierPayment::query()->create([
                'supplier_id' => $purchase->supplier_id,
                'purchase_id' => $purchase->id,
                'amount' => $amount,
                'payment_method_id' => $method->id,
                'paid_at' => $paidAt ?: now(),
                'reference' => $reference ?: null,
                'notes' => $notes ?: null,
                'paid_by' => Auth::id(),
            ]);
            $this->recalculate($purchase);
            $this->audit->log('supplier_payment.recorded', $payment, [], ['purchase' => $purchase->purchase_number, 'amount' => $amount, 'method' => $method->name]);

            return $payment;
        });
    }

    public function reversePayment(SupplierPayment $payment, string $reason): SupplierPayment
    {
        if (trim($reason) === '') {
            throw new DomainException(__('A reason is required to reverse a payment.'));
        }
        if ($payment->reversed_at) {
            throw new DomainException(__('This payment is already reversed.'));
        }

        return DB::transaction(function () use ($payment, $reason) {
            $payment->reversed_at = now();
            $payment->reversed_by = Auth::id();
            $payment->reversal_reason = $reason;
            $payment->save();
            if ($payment->purchase) {
                $this->recalculate($payment->purchase);
            }
            $this->audit->log('supplier_payment.reversed', $payment, [], ['amount' => $payment->amount], $reason);

            return $payment;
        });
    }

    public function recalculate(Purchase $purchase): Purchase
    {
        $subtotal = Money::of($purchase->items()->sum('line_total'));
        $total = Money::add($subtotal, $purchase->other_charges);
        $paid = Money::of($purchase->payments()->whereNull('reversed_at')->sum('amount'));

        $purchase->subtotal = $subtotal;
        $purchase->total = $total;
        $purchase->paid_amount = $paid;
        $purchase->balance_due = $purchase->status === PurchaseStatus::Cancelled ? '0.00' : Money::max(Money::sub($total, $paid), '0.00');
        $purchase->payment_status = PaymentStatus::fromAmounts($paid, $total);
        $purchase->save();

        return $purchase;
    }
}
