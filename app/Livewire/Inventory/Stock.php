<?php

namespace App\Livewire\Inventory;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Stock movements')]
class Stock extends Component
{
    use WithPagination;

    #[Url]
    public string $product = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        Gate::authorize('inventory');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $movements = StockMovement::query()->with(['product', 'user'])
            ->when($this->product, fn ($q) => $q->where('product_id', $this->product))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->from, fn ($q) => $q->where('created_at', '>=', $this->from.' 00:00:00'))
            ->when($this->to, fn ($q) => $q->where('created_at', '<=', $this->to.' 23:59:59'))
            ->latest('id')
            ->paginate(30);

        return view('livewire.inventory.stock', [
            'movements' => $movements,
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku']),
            'types' => StockMovementType::cases(),
        ]);
    }
}
