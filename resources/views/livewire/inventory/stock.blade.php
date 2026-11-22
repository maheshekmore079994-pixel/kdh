<div>
    <x-page-header :title="__('Stock movements')" :subtitle="__('Every change to stock, across all products.')" />

    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <flux:select wire:model.live="product" size="sm">
            <flux:select.option value="">{{ __('All products') }}</flux:select.option>
            @foreach($products as $p)<flux:select.option :value="$p->id">{{ $p->name }} ({{ $p->sku }})</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="type" size="sm">
            <flux:select.option value="">{{ __('All types') }}</flux:select.option>
            @foreach($types as $t)<flux:select.option :value="$t->value">{{ $t->label() }}</flux:select.option>@endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="from" size="sm" />
        <flux:input type="date" wire:model.live="to" size="sm" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$movements">
            <flux:table.columns>
                <flux:table.column>{{ __('When') }}</flux:table.column>
                <flux:table.column>{{ __('Product') }}</flux:table.column>
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
                        <flux:table.cell><a href="{{ route('products.show', $m->product) }}" class="hover:underline" wire:navigate>{{ $m->product?->name }}</a></flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="bccomp($m->quantity, '0', 3) >= 0 ? 'green' : 'red'">{{ $m->type->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ bccomp($m->quantity, '0', 3) >= 0 ? '+' : '' }}{{ qty($m->quantity) }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ qty($m->balance_after) }}</flux:table.cell>
                        <flux:table.cell class="text-sm">{{ $m->reason }}</flux:table.cell>
                        <flux:table.cell>{{ $m->user?->name }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="7"><x-empty-state icon="archive-box" :title="__('No movements match')" class="border-0" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
