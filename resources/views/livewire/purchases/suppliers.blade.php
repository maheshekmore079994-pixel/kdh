<div>
    <x-page-header :title="__('Suppliers')" :subtitle="__('Where you buy paper, toner and stationery.')">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search')" size="sm" class="w-56" />
        <flux:button wire:click="create" icon="plus" variant="primary" size="sm">{{ __('Add supplier') }}</flux:button>
    </x-page-header>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$suppliers">
            <flux:table.columns>
                <flux:table.column>{{ __('Supplier') }}</flux:table.column>
                <flux:table.column>{{ __('Contact') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Purchases') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Due to supplier') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($suppliers as $s)
                    <flux:table.row :key="$s->id">
                        <flux:table.cell><div class="font-medium">{{ $s->name }}</div>@if($s->gstin)<div class="text-xs text-zinc-500">GSTIN {{ $s->gstin }}</div>@endif @unless($s->is_active)<flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge>@endunless</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $s->phone }}@if($s->email)<div class="text-xs text-zinc-500">{{ $s->email }}</div>@endif</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ $s->purchases_count }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums {{ bccomp((string)($s->due ?? 0), '0', 2) > 0 ? 'font-medium text-amber-600' : 'text-zinc-400' }}">{{ money($s->due ?? 0) }}</flux:table.cell>
                        <flux:table.cell class="text-end whitespace-nowrap">
                            <flux:button :href="route('purchases.index', ['supplier' => $s->id])" size="xs" variant="ghost" icon="shopping-cart" wire:navigate>{{ __('Purchases') }}</flux:button>
                            <flux:button wire:click="edit({{ $s->id }})" size="xs" variant="ghost" icon="pencil-square" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="5"><x-empty-state icon="truck" :title="__('No suppliers yet')" class="border-0"><flux:button wire:click="create" variant="primary" size="sm">{{ __('Add supplier') }}</flux:button></x-empty-state></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="supplier-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit supplier') : __('New supplier') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Name')" required autofocus />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="phone" :label="__('Phone')" />
                <flux:input wire:model="email" :label="__('Email')" type="email" />
            </div>
            <flux:textarea wire:model="address" :label="__('Address')" rows="2" />
            <flux:input wire:model="gstin" :label="__('GSTIN (optional)')" />
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="2" />
            <flux:switch wire:model="isActive" :label="__('Active')" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
