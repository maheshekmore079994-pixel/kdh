<?php

namespace App\Livewire\Billing;

use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Payments')]
class Payments extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $method = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public bool $includeReversed = false;

    public function mount(): void
    {
        Gate::authorize('billing');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Payment::query()->with(['invoice.customer', 'method', 'receiver'])
            ->when(! $this->includeReversed, fn ($q) => $q->active())
            ->when($this->method, fn ($q) => $q->where('payment_method_id', $this->method))
            ->when($this->from, fn ($q) => $q->where('paid_at', '>=', $this->from.' 00:00:00'))
            ->when($this->to, fn ($q) => $q->where('paid_at', '<=', $this->to.' 23:59:59'))
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('payment_number', 'like', "%{$this->search}%")
                ->orWhere('reference', 'like', "%{$this->search}%")
                ->orWhereHas('invoice', fn ($i) => $i->where('invoice_number', 'like', "%{$this->search}%"))
                ->orWhereHas('invoice.customer', fn ($c) => $c->where('name', 'like', "%{$this->search}%")->orWhere('mobile', 'like', "%{$this->search}%"))))
            ->latest('paid_at')->latest('id');

        $total = (clone $query)->active()->sum('amount');

        return view('livewire.billing.payments', [
            'payments' => $query->paginate(25),
            'total' => $total,
            'methods' => PaymentMethod::query()->orderBy('sort_order')->get(),
        ]);
    }
}
