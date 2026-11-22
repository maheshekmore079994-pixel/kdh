<div>
    <x-page-header :title="__('Customers')" :subtitle="__('Name and mobile are enough. Keep other details optional.')">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search name, mobile or email')" size="sm" class="w-64" />
        <flux:button wire:click="create" icon="user-plus" variant="primary" size="sm">{{ __('Add customer') }}</flux:button>
    </x-page-header>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$customers">
            <flux:table.columns>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Mobile') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Bills') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Due') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($customers as $customer)
                    <flux:table.row :key="$customer->id">
                        <flux:table.cell>
                            <a href="{{ route('customers.show', $customer) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>{{ $customer->name }}</a>
                            @if($customer->email)<div class="text-xs text-zinc-500">{{ $customer->email }}</div>@endif
                        </flux:table.cell>
                        <flux:table.cell class="tabular-nums">{{ $customer->mobile }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ $customer->invoices_count }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums {{ bccomp((string) ($customer->due ?? 0), '0', 2) > 0 ? 'font-medium text-amber-600' : 'text-zinc-400' }}">{{ money($customer->due ?? 0) }}</flux:table.cell>
                        <flux:table.cell class="text-end whitespace-nowrap">
                            <flux:button :href="route('customers.show', $customer)" size="xs" variant="ghost" icon="eye" wire:navigate>{{ __('View') }}</flux:button>
                            <flux:button wire:click="edit({{ $customer->id }})" size="xs" variant="ghost" icon="pencil-square">{{ __('Edit') }}</flux:button>
                            @if($customer->invoices_count === 0)
                                <flux:button wire:click="delete({{ $customer->id }})" wire:confirm="{{ __('Remove this customer?') }}" size="xs" variant="ghost" icon="trash" />
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <x-empty-state icon="users" :title="$search ? __('No customers match') : __('No customers yet')" :description="$search ? null : __('Customers are also created automatically from the billing and job screens.')" class="border-0">
                                @unless($search)<flux:button wire:click="create" icon="user-plus" variant="primary" size="sm">{{ __('Add customer') }}</flux:button>@endunless
                            </x-empty-state>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="customer-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-5">
            <flux:heading size="lg">{{ $editingId ? __('Edit customer') : __('New customer') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Name')" required autofocus />
                <flux:input wire:model.live.debounce.400ms="mobile" :label="__('Mobile')" inputmode="numeric" maxlength="10" required />
            </div>
            @if($duplicate)
                <flux:callout icon="exclamation-triangle" variant="warning">
                    <flux:callout.text>{{ __('This mobile already belongs to :name.', ['name' => $duplicate->name]) }} <a href="{{ route('customers.show', $duplicate) }}" class="underline" wire:navigate>{{ __('Open that customer') }}</a></flux:callout.text>
                </flux:callout>
            @endif
            <flux:input wire:model="email" :label="__('Email (optional)')" type="email" />
            <flux:textarea wire:model="address" :label="__('Address (optional)')" rows="2" />
            <flux:textarea wire:model="notes" :label="__('Notes (optional)')" rows="2" :description="__('Never store passwords, OTPs, PINs or bank details here.')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
