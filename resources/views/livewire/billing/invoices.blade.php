<div>
    <x-page-header :title="__('Invoices')" :subtitle="__('All bills. Cancelled bills stay here for the record.')">
        <flux:button :href="route('pos')" icon="banknotes" variant="primary" size="sm" wire:navigate>{{ __('New bill') }}</flux:button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Invoice no., customer, mobile')" size="sm" />
        <flux:select wire:model.live="status" size="sm">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="due">{{ __('With balance due') }}</flux:select.option>
            @foreach($statuses as $s)
                <flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="from" size="sm" />
        <flux:input type="date" wire:model.live="to" size="sm" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$invoices">
            <flux:table.columns>
                <flux:table.column>{{ __('Invoice') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Total') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Paid') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Due') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($invoices as $inv)
                    <flux:table.row :key="$inv->id" class="{{ $inv->isCancelled() ? 'opacity-60' : '' }}">
                        <flux:table.cell><a href="{{ route('invoices.show', $inv) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>{{ $inv->invoice_number }}</a></flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $inv->invoice_date->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $inv->customer?->name ?? __('Walk-in') }}@if($inv->customer)<div class="text-xs text-zinc-500">{{ $inv->customer->mobile }}</div>@endif</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ money($inv->total) }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ money($inv->paid_amount) }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums {{ bccomp($inv->balance_due, '0', 2) > 0 ? 'text-amber-600 font-medium' : '' }}">{{ money($inv->balance_due) }}</flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$inv->status->color()">{{ $inv->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-end whitespace-nowrap">
                            <flux:button :href="route('print.invoice', [$inv, 'receipt'])" target="_blank" size="xs" variant="ghost" icon="printer" />
                            <flux:button :href="route('invoices.show', $inv)" size="xs" variant="ghost" icon="eye" wire:navigate>{{ __('Open') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="document-text" :title="__('No invoices found')" class="border-0"><flux:button :href="route('pos')" variant="primary" size="sm" wire:navigate>{{ __('Create the first bill') }}</flux:button></x-empty-state></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
