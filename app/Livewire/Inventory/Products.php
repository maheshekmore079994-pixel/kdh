<?php

namespace App\Livewire\Inventory;

use App\Exceptions\DomainException;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Services\AuditService;
use App\Services\StockService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Products')]
class Products extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = '';

    #[Url]
    public string $category = '';

    public ?int $editingId = null;

    public string $sku = '';

    public string $name = '';

    public ?int $categoryId = null;

    public string $unit = 'pcs';

    public string $openingStock = '0';

    public string $reorderLevel = '0';

    public string $purchaseCost = '';

    public string $sellingPrice = '';

    public bool $isSellable = true;

    public bool $isActive = true;

    public string $description = '';

    public function mount(): void
    {
        Gate::authorize('inventory');
    }

    public function updated($name): void
    {
        if (in_array($name, ['search', 'filter', 'category'], true)) {
            $this->resetPage();
        }
    }

    public function create(): void
    {
        Gate::authorize('inventory');
        $this->resetForm();
        $this->categoryId = ProductCategory::query()->where('is_active', true)->orderBy('sort_order')->value('id');
        Flux::modal('product-form')->show();
    }

    public function edit(int $id): void
    {
        Gate::authorize('inventory');
        $p = Product::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $p->id;
        $this->sku = $p->sku;
        $this->name = $p->name;
        $this->categoryId = $p->product_category_id;
        $this->unit = $p->unit;
        $this->openingStock = qty($p->opening_stock);
        $this->reorderLevel = qty($p->reorder_level);
        $this->purchaseCost = (string) $p->purchase_cost;
        $this->sellingPrice = $p->selling_price === null ? '' : (string) $p->selling_price;
        $this->isSellable = $p->is_sellable;
        $this->isActive = $p->is_active;
        $this->description = (string) $p->description;
        Flux::modal('product-form')->show();
    }

    public function save(StockService $stock, AuditService $audit): void
    {
        Gate::authorize('inventory');
        $v = $this->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->editingId)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:150'],
            'categoryId' => ['required', 'exists:product_categories,id'],
            'unit' => ['required', 'string', 'max:20'],
            'openingStock' => ['required', 'numeric', 'min:0'],
            'reorderLevel' => ['required', 'numeric', 'min:0'],
            'purchaseCost' => ['required', 'numeric', 'min:0'],
            'sellingPrice' => ['nullable', 'numeric', 'min:0'],
            'isSellable' => ['boolean'],
            'isActive' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $data = [
            'sku' => strtoupper(trim($v['sku'])),
            'name' => trim($v['name']),
            'product_category_id' => $v['categoryId'],
            'unit' => $v['unit'],
            'reorder_level' => number_format((float) $v['reorderLevel'], 3, '.', ''),
            'purchase_cost' => number_format((float) $v['purchaseCost'], 2, '.', ''),
            'selling_price' => $v['sellingPrice'] === '' || $v['sellingPrice'] === null ? null : number_format((float) $v['sellingPrice'], 2, '.', ''),
            'is_sellable' => $v['isSellable'],
            'is_active' => $v['isActive'],
            'description' => $v['description'] ?: null,
        ];

        try {
            DB::transaction(function () use ($data, $v, $stock, $audit) {
                if ($this->editingId) {
                    $p = Product::query()->findOrFail($this->editingId);
                    $p->fill($data);
                    $audit->logChanges('product.updated', $p);
                    $p->save();
                } else {
                    $p = Product::query()->create($data + ['opening_stock' => number_format((float) $v['openingStock'], 3, '.', ''), 'current_stock' => '0.000']);
                    $audit->log('product.created', $p, [], ['sku' => $p->sku, 'name' => $p->name, 'opening_stock' => $p->opening_stock]);
                    $stock->opening($p, $v['openingStock']);
                }
            });
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::modal('product-form')->close();
        $this->resetForm();
        Flux::toast(variant: 'success', text: __('Product saved.'));
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'sku', 'name', 'categoryId', 'unit', 'openingStock', 'reorderLevel', 'purchaseCost', 'sellingPrice', 'isSellable', 'isActive', 'description']);
        $this->resetValidation();
    }

    public function render(): View
    {
        $products = Product::query()->with('category')
            ->search($this->search)
            ->when($this->filter === 'low', fn ($q) => $q->lowStock())
            ->when($this->filter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->filter === '', fn ($q) => $q->where('is_active', true))
            ->when($this->category, fn ($q) => $q->where('product_category_id', $this->category))
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.inventory.products', [
            'products' => $products,
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'units' => Unit::query()->where('is_active', true)->orderBy('name')->get(),
            'lowCount' => Product::query()->lowStock()->count(),
        ]);
    }
}
