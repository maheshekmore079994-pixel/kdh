<div>
    <x-page-header :title="__('Expenses')" :subtitle="__('Shop running costs. Deleted expenses are kept in the audit log with the reason.')">
        <div class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800">{{ __('Total shown:') }} <span class="font-semibold tabular-nums">{{ money($total) }}</span></div>
        <flux:button wire:click="create" icon="plus" variant="primary" size="sm">{{ __('Add expense') }}</flux:button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Description, vendor, number')" size="sm" />
        <flux:select wire:model.live="category" size="sm">
            <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
            @foreach($categories as $c)<flux:select.option :value="$c->id">{{ $c->name }}</flux:select.option>@endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="from" size="sm" />
        <flux:input type="date" wire:model.live="to" size="sm" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$expenses">
            <flux:table.columns>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('No.') }}</flux:table.column>
                <flux:table.column>{{ __('Description') }}</flux:table.column>
                <flux:table.column>{{ __('Category') }}</flux:table.column>
                <flux:table.column>{{ __('Paid via') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Amount') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($expenses as $e)
                    <flux:table.row :key="$e->id">
                        <flux:table.cell class="whitespace-nowrap">{{ $e->expense_date->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-500">{{ $e->expense_number }}</flux:table.cell>
                        <flux:table.cell><div class="font-medium">{{ $e->description }}</div>@if($e->vendor)<div class="text-xs text-zinc-500">{{ $e->vendor }}</div>@endif</flux:table.cell>
                        <flux:table.cell>{{ $e->category?->name }}</flux:table.cell>
                        <flux:table.cell>{{ $e->method?->name }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums font-medium text-red-600">{{ money($e->amount) }}</flux:table.cell>
                        <flux:table.cell class="text-end whitespace-nowrap">
                            <flux:button wire:click="view({{ $e->id }})" size="xs" variant="ghost" icon="paper-clip" />
                            <flux:button wire:click="edit({{ $e->id }})" size="xs" variant="ghost" icon="pencil-square" />
                            <flux:button wire:click="startDelete({{ $e->id }})" size="xs" variant="ghost" icon="trash" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="7"><x-empty-state icon="receipt-percent" :title="__('No expenses recorded')" class="border-0"><flux:button wire:click="create" variant="primary" size="sm">{{ __('Add expense') }}</flux:button></x-empty-state></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="expense-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit expense') : __('Add expense') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="expenseDate" type="date" :label="__('Date')" required />
                <flux:select wire:model="categoryId" :label="__('Category')">@foreach($categories as $c)<flux:select.option :value="$c->id">{{ $c->name }}</flux:select.option>@endforeach</flux:select>
            </div>
            <flux:input wire:model="description" :label="__('Description')" required autofocus />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="amount" type="number" step="0.01" min="0.01" :label="__('Amount (₹)')" required />
                <flux:select wire:model="methodId" :label="__('Paid via')">@foreach($methods as $m)<flux:select.option :value="$m->id">{{ $m->name }}</flux:select.option>@endforeach</flux:select>
            </div>
            <flux:input wire:model="vendor" :label="__('Vendor / paid to (optional)')" />
            <flux:textarea wire:model="notes" :label="__('Notes (optional)')" rows="2" />
            @if($editingId)<flux:text class="text-xs">{{ __('Attach the bill from the paper-clip icon after saving.') }}</flux:text>@endif
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="expense-delete" class="w-full max-w-md">
        <form wire:submit="delete" class="space-y-4">
            <flux:heading size="lg">{{ __('Delete expense') }}</flux:heading>
            <flux:textarea wire:model="deleteReason" :label="__('Reason')" rows="2" required autofocus />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Keep') }}</flux:button></flux:modal.close><flux:button type="submit" variant="danger">{{ __('Delete') }}</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="expense-view" class="w-full max-w-lg">
        @if($viewing)
            <div class="space-y-4">
                <flux:heading size="lg">{{ $viewing->expense_number }} · {{ money($viewing->amount) }}</flux:heading>
                <flux:text>{{ $viewing->expense_date->format('d M Y') }} · {{ $viewing->category?->name }} · {{ $viewing->method?->name }} · {{ $viewing->creator?->name }}</flux:text>
                <flux:text>{{ $viewing->description }}@if($viewing->vendor) — {{ $viewing->vendor }}@endif</flux:text>
                @if($viewing->notes)<flux:text class="text-sm">{{ $viewing->notes }}</flux:text>@endif
                <livewire:documents.manager :model="$viewing" :key="'docs-expense-'.$viewing->id" />
            </div>
        @endif
    </flux:modal>
</div>
