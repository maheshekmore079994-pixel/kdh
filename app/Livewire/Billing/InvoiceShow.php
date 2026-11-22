<?php

namespace App\Livewire\Billing;

use App\Exceptions\DomainException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class InvoiceShow extends Component
{
    public Invoice $invoice;

    public string $payAmount = '';

    public ?int $payMethodId = null;

    public string $payReference = '';

    public string $payNotes = '';

    public string $cancelReason = '';

    public ?int $reversingPaymentId = null;

    public string $reverseReason = '';

    public ?string $autoPrint = null;

    public function mount(Invoice $invoice): void
    {
        Gate::authorize('billing');
        $this->invoice = $invoice;
        $this->payMethodId = PaymentMethod::query()->active()->value('id');
        $this->payAmount = (string) $invoice->balance_due;
        $this->autoPrint = in_array(request()->query('print'), ['receipt', 'a4'], true) ? request()->query('print') : null;
    }

    public function recordPayment(PaymentService $payments): void
    {
        Gate::authorize('billing');
        $this->validate([
            'payAmount' => ['required', 'numeric', 'gt:0'],
            'payMethodId' => ['required', 'exists:payment_methods,id'],
            'payReference' => ['nullable', 'string', 'max:100'],
            'payNotes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payments->record($this->invoice, $this->payAmount, (int) $this->payMethodId, null, $this->payReference ?: null, $this->payNotes ?: null);
        } catch (DomainException $e) {
            $this->addError('payAmount', $e->getMessage());

            return;
        }

        $this->invoice->refresh();
        $this->reset(['payReference', 'payNotes']);
        $this->payAmount = (string) $this->invoice->balance_due;
        Flux::modal('payment-form')->close();
        Flux::toast(variant: 'success', text: __('Payment recorded.'));
    }

    public function startReverse(int $paymentId): void
    {
        $this->reversingPaymentId = $paymentId;
        $this->reverseReason = '';
        $this->resetValidation();
        Flux::modal('reverse-form')->show();
    }

    public function reversePayment(PaymentService $payments): void
    {
        Gate::authorize('billing');
        $this->validate(['reverseReason' => ['required', 'string', 'min:3', 'max:500']]);
        $payment = Payment::query()->where('invoice_id', $this->invoice->id)->findOrFail($this->reversingPaymentId);

        try {
            $payments->reverse($payment, $this->reverseReason);
        } catch (DomainException $e) {
            $this->addError('reverseReason', $e->getMessage());

            return;
        }

        $this->invoice->refresh();
        $this->payAmount = (string) $this->invoice->balance_due;
        Flux::modal('reverse-form')->close();
        Flux::toast(variant: 'success', text: __('Payment reversed.'));
    }

    public function cancelInvoice(InvoiceService $invoices): void
    {
        Gate::authorize('billing');
        $this->validate(['cancelReason' => ['required', 'string', 'min:3', 'max:500']]);

        try {
            $invoices->cancel($this->invoice, $this->cancelReason);
        } catch (DomainException $e) {
            $this->addError('cancelReason', $e->getMessage());

            return;
        }

        $this->invoice->refresh();
        Flux::modal('cancel-form')->close();
        Flux::toast(variant: 'success', text: __('Invoice cancelled.'));
    }

    public function render(): View
    {
        $this->invoice->load(['items.product', 'items.service', 'payments.method', 'payments.receiver', 'customer', 'creator', 'canceller', 'job']);

        return view('livewire.billing.invoice-show', [
            'methods' => PaymentMethod::query()->active()->get(),
        ])->title($this->invoice->invoice_number);
    }
}
