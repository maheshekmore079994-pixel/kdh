<div>
    <x-page-header :title="__('Products')" :subtitle="__('Paper, toner, ink, stationery and anything sold or consumed. Stock only changes through recorded movements.')">
        @if($lowCount > 0)<flux:badge color="red">{{ __(':n low stock', ['n' => $lowCount]) }}</flux:badge>@endif
        <flux:button wire:click="create" icon="plus" variant="primary" size="sm">{{ __('Add product') }}</flux:button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Name or SKU')" size="sm" />
        <flux:select wire:model.live="category" size="sm">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach($categories as $c)<flux:select.option :value="$c->id">{{ $c->name }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="filter" size="sm">
            <flux:select.option value="">{{ __('Active products') }}</flux:select.option>
            <flux:select.option value="low">{{ __('Low stock only') }}</flux:select.option>
            <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$products">
            <flux:table.columns>
                <flux:table.column>{{ __('Product') }}</flux:table.column>
                <flux:table.column>{{ __('Category') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('In stock') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Reorder at') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Cost') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Sell price') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($products as $p)
                    <flux:table.row :key="$p->id">
                        <flux:table.cell><a href="{{ route('products.show', $p) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>{{ $p->name }}</a><div class="font-mono text-xs text-zinc-500">{{ $p->sku }}</div></flux:table.cell>
                        <flux:table.cell>{{ $p->category?->name }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums {{ $p->isLowStock() ? 'font-semibold text-red-600' : '' }}">{{ qty($p->current_stock) }} {{ $p->unit }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums text-zinc-500">{{ qty($p->reorder_level) }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ money($p->purchase_cost) }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ $p->selling_price !== null ? money($p->selling_price) : '—' }}</flux:table.cell>
                        <flux:table.cell class="text-end whitespace-nowrap">
                            <flux:button :href="route('products.show', $p)" size="xs" variant="ghost" icon="archive-box" wire:navigate>{{ __('Stock') }}</flux:button>
                            <flux:button wire:click="edit({{ $p->id }})" size="xs" variant="ghost" icon="pencil-square" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="7"><x-empty-state icon="cube" :title="__('No products')" :description="__('Add paper, toner, ink and stationery items to track stock and sell them on bills.')" class="border-0"><flux:button wire:click="create" variant="primary" size="sm">{{ __('Add product') }}</flux:button></x-empty-state></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="product-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit product') : __('New product') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="sku" :label="__('SKU / code')" required class="sm:col-span-1" />
                <flux:input wire:model="name" :label="__('Name')" required autofocus class="sm:col-span-2" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="categoryId" :label="__('Category')">@foreach($categories as $c)<flux:select.option :value="$c->id">{{ $c->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="unit" :label="__('Unit')">@foreach($units as $u)<flux:select.option :value="$u->abbreviation">{{ $u->name }} ({{ $u->abbreviation }})</flux:select.option>@endforeach</flux:select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @unless($editingId)
                    <flux:input wire:model="openingStock" type="number" step="any" min="0" :label="__('Opening stock')" :description="__('Recorded as an opening movement')" />
                @endunless
                <flux:input wire:model="reorderLevel" type="number" step="any" min="0" :label="__('Reorder level')" :description="__('Alert when stock is at or below this')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="purchaseCost" type="number" step="0.01" min="0" :label="__('Purchase cost per unit (₹)')" required :description="__('Used for stock valuation and profit estimate')" />
                <flux:input wire:model="sellingPrice" type="number" step="0.01" min="0" :label="__('Selling price (₹, optional)')" />
            </div>
            <flux:textarea wire:model="description" :label="__('Description')" rows="2" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:switch wire:model="isSellable" :label="__('Can be sold on bills')" />
                <flux:switch wire:model="isActive" :label="__('Active')" />
            </div>
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
