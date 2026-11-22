<?php

namespace App\Livewire\Inventory;

use App\Enums\StockMovementType;
use App\Exceptions\DomainException;
use App\Models\Product;
use App\Services\StockService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ProductShow extends Component
{
    use WithPagination;

    public Product $product;

    public string $movementType = 'consumption';

    public string $movementQty = '';

    public string $movementReason = '';

    public function mount(Product $product): void
    {
        Gate::authorize('inventory');
        $this->product = $product;
    }

    public function recordMovement(StockService $stock): void
    {
        Gate::authorize('inventory');
        $this->validate([
            'movementType' => ['required', 'in:consumption,adjustment,wastage'],
            'movementQty' => ['required', 'numeric', 'not_in:0'],
            'movementReason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $stock->manual($this->product, StockMovementType::from($this->movementType), $this->movementQty, $this->movementReason);
        } catch (DomainException $e) {
            $this->addError('movementQty', $e->getMessage());

            return;
        }

        $this->product->refresh();
        $this->reset(['movementQty', 'movementReason']);
        Flux::modal('movement-form')->close();
        Flux::toast(variant: 'success', text: __('Stock updated. Now :q :u.', ['q' => qty($this->product->current_stock), 'u' => $this->product->unit]));
    }

    public function render(StockService $stock): View
    {
        [$stored, $computed] = $stock->verify($this->product);

        return view('livewire.inventory.product-show', [
            'movements' => $this->product->movements()->with('user')->paginate(25),
            'integrityOk' => bccomp($stored, $computed, 3) === 0,
            'computed' => $computed,
        ])->title($this->product->name);
    }
}
