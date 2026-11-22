<div>
    <x-page-header :title="__('Lists & categories')" :subtitle="__('Reference lists used across billing, expenses and inventory. Inactive items are hidden from forms but kept for history.')">
        <flux:button wire:click="create" icon="plus" variant="primary" size="sm">{{ __('Add') }}</flux:button>
    </x-page-header>

    <flux:navbar class="mb-4 -mx-1 border-b border-zinc-200 dark:border-zinc-700">
        @foreach($tabs as $key => $label)
            <flux:navbar.item wire:click="$set('tab', '{{ $key }}')" :current="$tab === $key" class="cursor-pointer">{{ $label }}</flux:navbar.item>
        @endforeach
    </flux:navbar>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                @if($tab === 'payment-methods')
                    <flux:table.column>{{ __('Code') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                @elseif($tab === 'units')
                    <flux:table.column>{{ __('Abbreviation') }}</flux:table.column>
                @endif
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($rows as $row)
                    <flux:table.row :key="$row->id">
                        <flux:table.cell class="font-medium">{{ $row->name }}</flux:table.cell>
                        @if($tab === 'payment-methods')
                            <flux:table.cell class="font-mono text-xs">{{ $row->code }}</flux:table.cell>
                            <flux:table.cell>{{ $row->type->label() }}</flux:table.cell>
                        @elseif($tab === 'units')
                            <flux:table.cell class="font-mono text-xs">{{ $row->abbreviation }}</flux:table.cell>
                        @endif
                        <flux:table.cell><flux:badge size="sm" :color="$row->is_active ? 'green' : 'zinc'">{{ $row->is_active ? __('Active') : __('Inactive') }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-end whitespace-nowrap">
                            <flux:button wire:click="edit({{ $row->id }})" size="xs" variant="ghost" icon="pencil-square">{{ __('Edit') }}</flux:button>
                            <flux:button wire:click="toggle({{ $row->id }})" size="xs" variant="ghost">{{ $row->is_active ? __('Disable') : __('Enable') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="5"><x-empty-state :title="__('Nothing here yet')" class="border-0" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="list-form" class="w-full max-w-md">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? __('Edit') : __('Add') }} — {{ $tabs[$tab] }}</flux:heading>
            <flux:input wire:model="name" :label="__('Name')" required autofocus />
            @if($tab === 'payment-methods')
                <flux:input wire:model="code" :label="__('Code')" :description="__('Short unique identifier, e.g. cash, upi, paytm')" required />
                <flux:select wire:model="type" :label="__('Type')" :description="__('Used to group totals in the cashbook')">
                    @foreach($types as $t)
                        <flux:select.option :value="$t->value">{{ $t->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            @elseif($tab === 'units')
                <flux:input wire:model="code" :label="__('Abbreviation')" required />
            @endif
            <flux:switch wire:model="is_active" :label="__('Active')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
