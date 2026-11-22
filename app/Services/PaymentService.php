<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly InvoiceService $invoices,
        private readonly AuditService $audit,
    ) {}

    /** Record a customer payment against an invoice (full or partial). */
    public function record(
        Invoice $invoice,
        string|float $amount,
        int $paymentMethodId,
        ?string $paidAt = null,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        $amount = Money::of($amount);

        if (Money::cmp($amount, 0) <= 0) {
            throw new DomainException(__('Payment amount must be greater than zero.'));
        }
        if ($invoice->isCancelled()) {
            throw new DomainException(__('Payments cannot be recorded on a cancelled invoice.'));
        }
        if (Money::cmp($amount, $invoice->balance_due) > 0) {
            throw new DomainException(__('Amount exceeds the balance due (:due).', ['due' => money($invoice->balance_due)]));
        }

        $method = PaymentMethod::query()->where('is_active', true)->findOrFail($paymentMethodId);

        return DB::transaction(function () use ($invoice, $amount, $method, $paidAt, $reference, $notes) {
            $payment = Payment::query()->create([
                'payment_number' => $this->numbering->next('payment'),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $amount,
                'payment_method_id' => $method->id,
                'paid_at' => $paidAt ?: now(),
                'reference' => $reference ?: null,
                'notes' => $notes ?: null,
                'received_by' => Auth::id(),
            ]);

            $this->invoices->recalculate($invoice);

            $this->audit->log('payment.recorded', $payment, [], [
                'payment_number' => $payment->payment_number, 'invoice' => $invoice->invoice_number,
                'amount' => $amount, 'method' => $method->name,
            ]);

            return $payment;
        });
    }

    /** Reverse a payment (e.g. recorded by mistake). The row is kept with the reason. */
    public function reverse(Payment $payment, string $reason): Payment
    {
        if (trim($reason) === '') {
            throw new DomainException(__('A reason is required to reverse a payment.'));
        }
        if ($payment->isReversed()) {
            throw new DomainException(__('This payment is already reversed.'));
        }

        return DB::transaction(function () use ($payment, $reason) {
            $payment->reversed_at = now();
            $payment->reversed_by = Auth::id();
            $payment->reversal_reason = $reason;
            $payment->save();

            $this->invoices->recalculate($payment->invoice);

            $this->audit->log('payment.reversed', $payment, ['reversed' => false], ['reversed' => true, 'amount' => $payment->amount], $reason);

            return $payment;
        });
    }
}
