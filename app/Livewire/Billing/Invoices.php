<?php

namespace App\Livewire\Billing;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Invoices')]
class Invoices extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

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
        $invoices = Invoice::query()->with('customer')
            ->search($this->search)
            ->when($this->status === 'due', fn ($q) => $q->notCancelled()->where('balance_due', '>', 0))
            ->when($this->status && $this->status !== 'due', fn ($q) => $q->where('status', $this->status))
            ->when($this->from, fn ($q) => $q->whereDate('invoice_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('invoice_date', '<=', $this->to))
            ->latest('invoice_date')->latest('id')
            ->paginate(20);

        return view('livewire.billing.invoices', ['invoices' => $invoices, 'statuses' => InvoiceStatus::cases()]);
    }
}
