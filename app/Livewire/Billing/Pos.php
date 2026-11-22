<?php

namespace App\Livewire\Billing;

use App\Exceptions\DomainException;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Service;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use App\Support\Money;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New bill')]
class Pos extends Component
{
    public ?int $customerId = null;

    public string $search = '';

    /** @var list<array<string, mixed>> */
    public array $lines = [];

    public string $discount = '';

    public string $notes = '';

    public bool $applyTax = false;

    public string $paymentAmount = '';

    public ?int $paymentMethodId = null;

    public string $paymentReference = '';

    public function mount(SettingsService $settings): void
    {
        Gate::authorize('billing');
        $this->customerId = request()->integer('customer') ?: null;
        $this->applyTax = $settings->taxEnabled();
        $this->paymentMethodId = PaymentMethod::query()->active()->value('id');
    }

    public function addService(int $serviceId, ?int $variantId = null): void
    {
        $service = Service::query()->with('variants')->findOrFail($serviceId);
        $variant = $variantId ? $service->variants->firstWhere('id', $variantId) : null;
        $price = $variant?->unit_price ?? $service->default_price;

        $this->lines[] = [
            'type' => 'service',
            'service_id' => $service->id,
            'service_variant_id' => $variant?->id,
            'product_id' => null,
            'description' => $service->name.($variant ? ' – '.$variant->name : ''),
            'quantity' => $variant ? qty($variant->min_quantity) : '1',
            'unit' => $service->unit,
            'unit_price' => $price === null ? '' : (string) $price,
            'discount_amount' => '',
        ];
        $this->search = '';
    }

    public function addProduct(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        foreach ($this->lines as $i => $line) {
            if ($line['type'] === 'product' && $line['product_id'] === $product->id) {
                $this->lines[$i]['quantity'] = qty(bcadd((string) $line['quantity'], '1', 3));
                $this->search = '';

                return;
            }
        }

        $this->lines[] = [
            'type' => 'product',
            'service_id' => null,
            'service_variant_id' => null,
            'product_id' => $product->id,
            'description' => $product->name,
            'quantity' => '1',
            'unit' => $product->unit,
            'unit_price' => $product->selling_price === null ? '' : (string) $product->selling_price,
            'discount_amount' => '',
        ];
        $this->search = '';
    }

    public function addCustomLine(): void
    {
        $this->lines[] = [
            'type' => 'service', 'service_id' => null, 'service_variant_id' => null, 'product_id' => null,
            'description' => '', 'quantity' => '1', 'unit' => 'pcs', 'unit_price' => '', 'discount_amount' => '',
        ];
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function payFull(): void
    {
        $this->paymentAmount = $this->totals()['total'];
    }

    /** @return array{subtotal: string, discount: string, tax: string, total: string, tax_rate: ?string} */
    public function totals(): array
    {
        $subtotal = '0.00';
        foreach ($this->lines as $line) {
            $gross = Money::mul((float) ($line['quantity'] ?: 0), (float) ($line['unit_price'] ?: 0));
            $subtotal = Money::add($subtotal, Money::sub($gross, (float) ($line['discount_amount'] ?: 0)));
        }
        $discount = Money::of((float) ($this->discount ?: 0));
        $taxable = Money::max(Money::sub($subtotal, $discount), '0.00');
        $rate = $this->applyTax && app(SettingsService::class)->taxEnabled() ? (string) setting('tax.rate') : null;
        $tax = $rate !== null ? Money::percent($taxable, $rate) : '0.00';

        return ['subtotal' => $subtotal, 'discount' => $discount, 'tax' => $tax, 'total' => Money::add($taxable, $tax), 'tax_rate' => $rate];
    }

    public function save(InvoiceService $invoices, PaymentService $payments): void
    {
        Gate::authorize('billing');

        $this->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'paymentAmount' => ['nullable', 'numeric', 'min:0'],
            'paymentMethodId' => ['required_with:paymentAmount', 'nullable', 'exists:payment_methods,id'],
            'customerId' => ['nullable', 'exists:customers,id'],
        ], [
            'lines.required' => __('Add at least one item to the bill.'),
            'lines.*.unit_price.required' => __('Enter a price for every item.'),
            'lines.*.description.required' => __('Describe every item.'),
        ]);

        try {
            $invoice = DB::transaction(function () use ($invoices, $payments) {
                $invoice = $invoices->create([
                    'customer_id' => $this->customerId,
                    'discount_amount' => $this->discount ?: 0,
                    'notes' => $this->notes ?: null,
                    'apply_tax' => $this->applyTax,
                ], $this->lines);

                $amount = Money::of((float) ($this->paymentAmount ?: 0));
                if (Money::cmp($amount, 0) > 0) {
                    $payments->record($invoice, $amount, (int) $this->paymentMethodId, null, $this->paymentReference ?: null);
                }

                $invoice->refresh();
                $invoices->assertWalkInSettled($invoice);

                return $invoice;
            });
        } catch (DomainException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: __('Bill :n saved.', ['n' => $invoice->invoice_number]));
        $this->redirectRoute('invoices.show', ['invoice' => $invoice, 'print' => setting('invoice.default_print', 'receipt')], navigate: true);
    }

    public function render(): View
    {
        $term = trim($this->search);
        $services = collect();
        $products = collect();

        if ($term === '' || strlen($term) >= 1) {
            $services = Service::query()->active()->with(['variants' => fn ($q) => $q->where('is_active', true)])
                ->when($term !== '', fn ($q) => $q->search($term))
                ->orderBy('sort_order')->orderBy('name')->limit($term === '' ? 12 : 20)->get();
            $products = Product::query()->where('is_active', true)->where('is_sellable', true)
                ->when($term !== '', fn ($q) => $q->search($term))
                ->orderBy('name')->limit($term === '' ? 8 : 20)->get();
        }

        return view('livewire.billing.pos', [
            'services' => $services,
            'products' => $products,
            'methods' => PaymentMethod::query()->active()->get(),
            'totals' => $this->totals(),
            'taxEnabled' => app(SettingsService::class)->taxEnabled(),
        ]);
    }
}
