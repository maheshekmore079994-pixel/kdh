<?php

namespace App\Livewire\Purchases;

use App\Exceptions\DomainException;
use App\Models\PaymentMethod;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Services\PurchaseService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Show extends Component
{
    public Purchase $purchase;

    public string $payAmount = '';

    public ?int $payMethodId = null;

    public string $payReference = '';

    public string $cancelReason = '';

    public ?int $reversingId = null;

    public string $reverseReason = '';

    public function mount(Purchase $purchase): void
    {
        Gate::authorize('purchases');
        $this->purchase = $purchase;
        $this->payMethodId = PaymentMethod::query()->active()->value('id');
        $this->payAmount = (string) $purchase->balance_due;
    }

    public function post(PurchaseService $purchases): void
    {
        Gate::authorize('purchases');
        try {
            $purchases->post($this->purchase);
            Flux::toast(variant: 'success', text: __('Purchase posted. Stock updated.'));
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
        $this->purchase->refresh();
    }

    public function recordPayment(PurchaseService $purchases): void
    {
        Gate::authorize('purchases');
        $this->validate(['payAmount' => ['required', 'numeric', 'gt:0'], 'payMethodId' => ['required', 'exists:payment_methods,id'], 'payReference' => ['nullable', 'string', 'max:100']]);
        try {
            $purchases->recordPayment($this->purchase, $this->payAmount, (int) $this->payMethodId, null, $this->payReference ?: null);
        } catch (DomainException $e) {
            $this->addError('payAmount', $e->getMessage());

            return;
        }
        $this->purchase->refresh();
        $this->payAmount = (string) $this->purchase->balance_due;
        $this->payReference = '';
        Flux::modal('supplier-payment')->close();
        Flux::toast(variant: 'success', text: __('Supplier payment recorded.'));
    }

    public function startReverse(int $id): void
    {
        $this->reversingId = $id;
        $this->reverseReason = '';
        Flux::modal('reverse-supplier-payment')->show();
    }

    public function reversePayment(PurchaseService $purchases): void
    {
        Gate::authorize('purchases');
        $this->validate(['reverseReason' => ['required', 'string', 'min:3', 'max:500']]);
        $payment = SupplierPayment::query()->where('purchase_id', $this->purchase->id)->findOrFail($this->reversingId);
        try {
            $purchases->reversePayment($payment, $this->reverseReason);
        } catch (DomainException $e) {
            $this->addError('reverseReason', $e->getMessage());

            return;
        }
        $this->purchase->refresh();
        $this->payAmount = (string) $this->purchase->balance_due;
        Flux::modal('reverse-supplier-payment')->close();
        Flux::toast(variant: 'success', text: __('Supplier payment reversed.'));
    }

    public function cancel(PurchaseService $purchases): void
    {
        Gate::authorize('purchases');
        $this->validate(['cancelReason' => ['required', 'string', 'min:3', 'max:500']]);
        try {
            $purchases->cancel($this->purchase, $this->cancelReason);
        } catch (DomainException $e) {
            $this->addError('cancelReason', $e->getMessage());

            return;
        }
        $this->purchase->refresh();
        Flux::modal('cancel-purchase')->close();
        Flux::toast(variant: 'success', text: __('Purchase cancelled and stock reversed.'));
    }

    public function render(): View
    {
        $this->purchase->load(['supplier', 'items.product', 'payments.method', 'payments.payer', 'creator']);

        return view('livewire.purchases.show', ['methods' => PaymentMethod::query()->active()->get()])->title($this->purchase->purchase_number);
    }
}
