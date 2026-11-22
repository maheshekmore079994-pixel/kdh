<?php

namespace App\Livewire\Purchases;

use App\Exceptions\DomainException;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New purchase')]
class Form extends Component
{
    public ?int $supplierId = null;

    public string $supplierInvoiceNo = '';

    public string $purchaseDate = '';

    public string $otherCharges = '';

    public string $notes = '';

    public string $productSearch = '';

    /** @var list<array{product_id:int, name:string, unit:string, quantity:string, unit_cost:string}> */
    public array $items = [];

    public string $paidNow = '';

    public ?int $paymentMethodId = null;

    public string $paymentReference = '';

    public function mount(): void
    {
        Gate::authorize('purchases');
        $this->purchaseDate = now()->toDateString();
        $this->paymentMethodId = PaymentMethod::query()->active()->value('id');
    }

    public function addProduct(int $id): void
    {
        $p = Product::query()->findOrFail($id);
        foreach ($this->items as $item) {
            if ($item['product_id'] === $p->id) {
                $this->productSearch = '';

                return;
            }
        }
        $this->items[] = ['product_id' => $p->id, 'name' => $p->name.' ('.$p->sku.')', 'unit' => $p->unit, 'quantity' => '1', 'unit_cost' => (string) $p->purchase_cost];
        $this->productSearch = '';
    }

    public function removeItem(int $i): void
    {
        unset($this->items[$i]);
        $this->items = array_values($this->items);
    }

    public function totals(): array
    {
        $subtotal = '0.00';
        foreach ($this->items as $item) {
            $subtotal = Money::add($subtotal, Money::mul((float) ($item['quantity'] ?: 0), (float) ($item['unit_cost'] ?: 0)));
        }

        return ['subtotal' => $subtotal, 'total' => Money::add($subtotal, (float) ($this->otherCharges ?: 0))];
    }

    public function save(PurchaseService $purchases): void
    {
        Gate::authorize('purchases');
        $this->validate([
            'supplierId' => ['required', 'exists:suppliers,id'],
            'supplierInvoiceNo' => ['nullable', 'string', 'max:60'],
            'purchaseDate' => ['required', 'date', 'before_or_equal:today'],
            'otherCharges' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'paidNow' => ['nullable', 'numeric', 'min:0'],
            'paymentMethodId' => ['required_with:paidNow', 'nullable', 'exists:payment_methods,id'],
        ], ['items.required' => __('Add at least one item.')]);

        try {
            $purchase = DB::transaction(function () use ($purchases) {
                $purchase = $purchases->create([
                    'supplier_id' => $this->supplierId,
                    'supplier_invoice_no' => $this->supplierInvoiceNo ?: null,
                    'purchase_date' => $this->purchaseDate,
                    'other_charges' => $this->otherCharges ?: 0,
                    'notes' => $this->notes ?: null,
                ], $this->items, post: true);

                if (Money::cmp((float) ($this->paidNow ?: 0), 0) > 0) {
                    $purchases->recordPayment($purchase, $this->paidNow, (int) $this->paymentMethodId, null, $this->paymentReference ?: null);
                }

                return $purchase;
            });
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: __('Purchase :n posted and stock updated.', ['n' => $purchase->purchase_number]));
        $this->redirectRoute('purchases.show', $purchase, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.purchases.form', [
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => strlen($this->productSearch) >= 1 ? Product::query()->where('is_active', true)->search($this->productSearch)->orderBy('name')->limit(10)->get() : collect(),
            'methods' => PaymentMethod::query()->active()->get(),
            'totals' => $this->totals(),
        ]);
    }
}
