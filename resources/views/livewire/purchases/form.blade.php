<div>
    <x-page-header :title="__('New purchase')" :subtitle="__('Saving posts the purchase and adds the items to stock. Product cost is updated to the latest purchase cost.')">
        <flux:button :href="route('purchases.index')" variant="ghost" size="sm" wire:navigate>{{ __('Cancel') }}</flux:button>
    </x-page-header>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model="supplierId" :label="__('Supplier')" :placeholder="__('Choose')">
                    <flux:select.option value="">{{ __('Select supplier') }}</flux:select.option>
                    @foreach($suppliers as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach
                </flux:select>
                <flux:input wire:model="supplierInvoiceNo" :label="__('Supplier bill no.')" />
                <flux:input wire:model="purchaseDate" type="date" :label="__('Purchase date')" required />
            </div>
            @if($suppliers->isEmpty())<flux:callout variant="warning" icon="exclamation-triangle"><flux:callout.text>{{ __('No suppliers yet.') }} <a href="{{ route('suppliers.index') }}" class="underline" wire:navigate>{{ __('Add a supplier first.') }}</a></flux:callout.text></flux:callout>@endif

            <div>
                <flux:input wire:model.live.debounce.200ms="productSearch" icon="magnifying-glass" :label="__('Add items')" :placeholder="__('Type product name or SKU')" autocomplete="off" />
                @if($products->isNotEmpty())
                    <div class="mt-1 overflow-hidden rounded-lg border border-zinc-200 bg-white text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                        @foreach($products as $p)
                            <button type="button" wire:click="addProduct({{ $p->id }})" class="flex w-full items-center justify-between px-3 py-2 text-start hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <span>{{ $p->name }} <span class="text-xs text-zinc-500">{{ $p->sku }}</span></span>
                                <span class="text-xs text-zinc-500">{{ __('stock') }} {{ qty($p->current_stock) }} · {{ __('last cost') }} {{ money($p->purchase_cost) }}</span>
                            </button>
                        @endforeach
                    </div>
                @elseif(strlen($productSearch) >= 1)
                    <flux:text class="mt-1 text-xs">{{ __('No product found.') }} <a href="{{ route('products.index') }}" class="underline" wire:navigate>{{ __('Add it under Products.') }}</a></flux:text>
                @endif
            </div>
            <flux:error name="items" />

            <table class="w-full text-sm">
                <thead class="text-xs uppercase text-zinc-500"><tr><th class="pb-2 text-start">{{ __('Product') }}</th><th class="w-28 pb-2 text-end">{{ __('Qty') }}</th><th class="w-32 pb-2 text-end">{{ __('Unit cost') }}</th><th class="w-28 pb-2 text-end">{{ __('Amount') }}</th><th class="w-8"></th></tr></thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @forelse($items as $i => $item)
                        <tr wire:key="pi-{{ $i }}">
                            <td class="py-2 pr-2">{{ $item['name'] }} <span class="text-xs text-zinc-500">/ {{ $item['unit'] }}</span></td>
                            <td class="py-2 pr-2"><flux:input wire:model.live.debounce.300ms="items.{{ $i }}.quantity" type="number" step="any" min="0" size="sm" class="text-end" /></td>
                            <td class="py-2 pr-2"><flux:input wire:model.live.debounce.300ms="items.{{ $i }}.unit_cost" type="number" step="0.01" min="0" size="sm" class="text-end" /></td>
                            <td class="py-2 text-end tabular-nums font-medium">{{ money(\App\Support\Money::mul((float)($item['quantity'] ?: 0), (float)($item['unit_cost'] ?: 0))) }}</td>
                            <td class="py-2 text-end"><flux:button wire:click="removeItem({{ $i }})" size="xs" variant="ghost" icon="x-mark" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-zinc-500">{{ __('No items added yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model.live.debounce.300ms="otherCharges" type="number" step="0.01" min="0" :label="__('Other charges (transport etc.)')" size="sm" />
                <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" size="sm" />
            </div>
        </div>

        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 self-start dark:border-zinc-700 dark:bg-zinc-800">
            <div class="space-y-1 text-sm">
                <div class="flex justify-between"><span class="text-zinc-500">{{ __('Items') }}</span><span class="tabular-nums">{{ money($totals['subtotal']) }}</span></div>
                <div class="flex justify-between border-t border-zinc-200 pt-2 text-lg font-semibold dark:border-zinc-700"><span>{{ __('Total') }}</span><span class="tabular-nums">{{ money($totals['total']) }}</span></div>
            </div>
            <flux:separator />
            <flux:input wire:model.live.debounce.300ms="paidNow" type="number" step="0.01" min="0" :label="__('Paid to supplier now (₹)')" size="sm" />
            <flux:select wire:model="paymentMethodId" :label="__('Method')" size="sm">@foreach($methods as $m)<flux:select.option :value="$m->id">{{ $m->name }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="paymentReference" :label="__('Reference')" size="sm" />
            <div class="flex justify-between text-sm"><span class="text-zinc-500">{{ __('Balance to supplier') }}</span><span class="tabular-nums font-semibold">{{ money(\App\Support\Money::max(\App\Support\Money::sub($totals['total'], (float)($paidNow ?: 0)), '0')) }}</span></div>
            <flux:button type="submit" variant="primary" class="w-full" icon="check">{{ __('Post purchase') }}</flux:button>
        </div>
    </form>
</div>
