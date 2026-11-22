<div>
    <x-page-header :title="$product->name" :subtitle="$product->sku.' · '.$product->category?->name">
        <flux:modal.trigger name="movement-form"><flux:button icon="adjustments-horizontal" variant="primary" size="sm">{{ __('Record stock change') }}</flux:button></flux:modal.trigger>
        <flux:button :href="route('products.index')" variant="ghost" size="sm" wire:navigate>{{ __('All products') }}</flux:button>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-stat-card :label="__('In stock')" :value="qty($product->current_stock).' '.$product->unit" :color="$product->isLowStock() ? 'red' : 'green'" icon="archive-box" :hint="$product->isLowStock() ? __('At or below reorder level') : null" />
        <x-stat-card :label="__('Reorder level')" :value="qty($product->reorder_level).' '.$product->unit" icon="exclamation-triangle" />
        <x-stat-card :label="__('Purchase cost')" :value="money($product->purchase_cost)" icon="shopping-cart" />
        <x-stat-card :label="__('Stock value')" :value="money(\App\Support\Money::mul($product->current_stock, $product->purchase_cost))" icon="currency-rupee" />
    </div>

    @unless($integrityOk)
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4"><flux:callout.text>{{ __('Stock balance (:s) does not match the movement history (:c). Record an adjustment with a reason to correct it.', ['s' => qty($product->current_stock), 'c' => qty($computed)]) }}</flux:callout.text></flux:callout>
    @endunless

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$movements">
            <flux:table.columns>
                <flux:table.column>{{ __('When') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Change') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Balance') }}</flux:table.column>
                <flux:table.column>{{ __('Reference / reason') }}</flux:table.column>
                <flux:table.column>{{ __('By') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($movements as $m)
                    <flux:table.row :key="$m->id">
                        <flux:table.cell class="whitespace-nowrap">{{ $m->created_at->format('d M Y, h:i A') }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="bccomp($m->quantity, '0', 3) >= 0 ? 'green' : 'red'">{{ $m->type->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums {{ bccomp($m->quantity, '0', 3) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ bccomp($m->quantity, '0', 3) >= 0 ? '+' : '' }}{{ qty($m->quantity) }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ qty($m->balance_after) }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $m->reason }}</flux:table.cell>
                        <flux:table.cell>{{ $m->user?->name }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="6"><x-empty-state icon="archive-box" :title="__('No stock movements yet')" class="border-0" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="movement-form" class="w-full max-w-md">
        <form wire:submit="recordMovement" class="space-y-4">
            <flux:heading size="lg">{{ __('Record stock change') }}</flux:heading>
            <flux:text class="text-sm">{{ __('Purchases and sales update stock automatically. Use this for consumption, wastage or corrections.') }}</flux:text>
            <flux:select wire:model.live="movementType" :label="__('Type')">
                <flux:select.option value="consumption">{{ __('Consumption (used in the shop)') }}</flux:select.option>
                <flux:select.option value="wastage">{{ __('Wastage / damaged') }}</flux:select.option>
                <flux:select.option value="adjustment">{{ __('Adjustment (+ or −) after counting') }}</flux:select.option>
            </flux:select>
            <flux:input wire:model="movementQty" type="number" step="any" :label="$movementType === 'adjustment' ? __('Quantity (negative to reduce)') : __('Quantity removed')" required autofocus />
            <flux:textarea wire:model="movementReason" :label="__('Reason')" rows="2" required />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ __('Record') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
