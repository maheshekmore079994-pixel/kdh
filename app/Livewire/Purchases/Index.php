<?php

namespace App\Livewire\Purchases;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Purchases')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $supplier = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('purchases');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $purchases = Purchase::query()->with('supplier')
            ->search($this->search)
            ->when($this->supplier, fn ($q) => $q->where('supplier_id', $this->supplier))
            ->when($this->status === 'due', fn ($q) => $q->where('status', 'posted')->where('balance_due', '>', 0))
            ->when($this->status && $this->status !== 'due', fn ($q) => $q->where('status', $this->status))
            ->when($this->from, fn ($q) => $q->whereDate('purchase_date', '>=', $this->from))
            ->when($this->to, fn ($q) => $q->whereDate('purchase_date', '<=', $this->to))
            ->latest('purchase_date')->latest('id')
            ->paginate(20);

        return view('livewire.purchases.index', [
            'purchases' => $purchases,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => PurchaseStatus::cases(),
        ]);
    }
}
