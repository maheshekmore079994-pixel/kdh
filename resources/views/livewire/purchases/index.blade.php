<div>
    <x-page-header :title="__('Purchases')" :subtitle="__('Stock bought from suppliers. Posting a purchase adds the items to stock.')">
        <flux:button :href="route('purchases.create')" icon="plus" variant="primary" size="sm" wire:navigate>{{ __('New purchase') }}</flux:button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Number, supplier bill, supplier')" size="sm" />
        <flux:select wire:model.live="supplier" size="sm">
            <flux:select.option value="">{{ __('All suppliers') }}</flux:select.option>
            @foreach($suppliers as $s)<flux:select.option :value="$s->id">{{ $s->name }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model.live="status" size="sm">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="due">{{ __('With balance due') }}</flux:select.option>
            @foreach($statuses as $st)<flux:select.option :value="$st->value">{{ $st->label() }}</flux:select.option>@endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="from" size="sm" />
        <flux:input type="date" wire:model.live="to" size="sm" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$purchases">
            <flux:table.columns>
                <flux:table.column>{{ __('Purchase') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Supplier') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Total') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Paid') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Due') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($purchases as $p)
                    <flux:table.row :key="$p->id">
                        <flux:table.cell><a href="{{ route('purchases.show', $p) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>{{ $p->purchase_number }}</a>@if($p->supplier_invoice_no)<div class="text-xs text-zinc-500">{{ __('Bill') }} {{ $p->supplier_invoice_no }}</div>@endif</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $p->purchase_date->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $p->supplier?->name }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ money($p->total) }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ money($p->paid_amount) }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums {{ bccomp($p->balance_due, '0', 2) > 0 ? 'text-amber-600 font-medium' : '' }}">{{ money($p->balance_due) }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$p->status->color()">{{ $p->status->label() }}</flux:badge> <flux:badge size="sm" :color="$p->payment_status->color()">{{ $p->payment_status->label() }}</flux:badge></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="7"><x-empty-state icon="shopping-cart" :title="__('No purchases recorded')" class="border-0"><flux:button :href="route('purchases.create')" variant="primary" size="sm" wire:navigate>{{ __('Record a purchase') }}</flux:button></x-empty-state></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
