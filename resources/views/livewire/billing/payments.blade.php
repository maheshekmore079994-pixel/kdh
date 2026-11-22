<div>
    <x-page-header :title="__('Payments')" :subtitle="__('Money actually received from customers. Reversed payments are excluded from totals.')">
        <div class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">{{ __('Total shown:') }} <span class="font-semibold tabular-nums">{{ money($total) }}</span></div>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Receipt, invoice, customer, reference')" size="sm" class="sm:col-span-2" />
        <flux:select wire:model.live="method" size="sm">
            <flux:select.option value="">{{ __('All methods') }}</flux:select.option>
            @foreach($methods as $m)<flux:select.option :value="$m->id">{{ $m->name }}</flux:select.option>@endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="from" size="sm" />
        <flux:input type="date" wire:model.live="to" size="sm" />
    </div>
    <div class="mb-3"><flux:checkbox wire:model.live="includeReversed" :label="__('Show reversed payments')" /></div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$payments">
            <flux:table.columns>
                <flux:table.column>{{ __('Receipt') }}</flux:table.column>
                <flux:table.column>{{ __('When') }}</flux:table.column>
                <flux:table.column>{{ __('Invoice') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Method') }}</flux:table.column>
                <flux:table.column>{{ __('Received by') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Amount') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($payments as $p)
                    <flux:table.row :key="$p->id" class="{{ $p->isReversed() ? 'opacity-50' : '' }}">
                        <flux:table.cell class="font-medium">{{ $p->payment_number }}@if($p->isReversed()) <flux:badge size="sm" color="zinc">{{ __('Reversed') }}</flux:badge>@endif</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">{{ $p->paid_at->format('d M Y, h:i A') }}</flux:table.cell>
                        <flux:table.cell><a href="{{ route('invoices.show', $p->invoice) }}" class="hover:underline" wire:navigate>{{ $p->invoice?->invoice_number }}</a></flux:table.cell>
                        <flux:table.cell>{{ $p->invoice?->customer?->name ?? __('Walk-in') }}</flux:table.cell>
                        <flux:table.cell>{{ $p->method?->name }}@if($p->reference)<div class="text-xs text-zinc-500">{{ $p->reference }}</div>@endif</flux:table.cell>
                        <flux:table.cell>{{ $p->receiver?->name }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums font-medium">{{ money($p->amount) }}</flux:table.cell>
                        <flux:table.cell class="text-end"><flux:button :href="route('print.payment', $p)" target="_blank" size="xs" variant="ghost" icon="printer" /></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="currency-rupee" :title="__('No payments found')" class="border-0" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
