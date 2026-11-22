<div class="space-y-2">
    @if($selected)
        <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="min-w-0">
                <div class="truncate font-medium text-zinc-900 dark:text-white">{{ $selected->name }}</div>
                <div class="text-xs text-zinc-500">{{ $selected->mobile }}@if($selected->email) · {{ $selected->email }}@endif</div>
            </div>
            <flux:button wire:click="clear" size="xs" variant="ghost" icon="x-mark">{{ __('Change') }}</flux:button>
        </div>
    @elseif($creating)
        <div class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
            <flux:text class="text-sm font-medium">{{ __('New customer') }}</flux:text>
            <flux:input wire:model="newName" :placeholder="__('Name')" size="sm" wire:keydown.enter.prevent="create" />
            <flux:input wire:model="newMobile" :placeholder="__('10-digit mobile')" size="sm" inputmode="numeric" wire:keydown.enter.prevent="create" />
            <div class="flex gap-2">
                <flux:button wire:click="create" size="sm" variant="primary">{{ __('Save customer') }}</flux:button>
                <flux:button wire:click="$set('creating', false)" size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
            </div>
        </div>
    @else
        <flux:input wire:model.live.debounce.250ms="search" icon="magnifying-glass" :placeholder="$required ? __('Customer name or mobile (required)') : __('Customer name or mobile (optional for walk-in)')" size="sm" autocomplete="off" />
        @if(strlen($search) >= 2)
            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                @foreach($results as $customer)
                    <button type="button" wire:click="select({{ $customer->id }})" class="flex w-full items-center justify-between px-3 py-2 text-start hover:bg-zinc-50 dark:hover:bg-zinc-700">
                        <span class="font-medium">{{ $customer->name }}</span>
                        <span class="text-xs text-zinc-500">{{ $customer->mobile }}</span>
                    </button>
                @endforeach
                <button type="button" wire:click="startCreate" class="flex w-full items-center gap-2 border-t border-zinc-100 px-3 py-2 text-start text-[#B9821F] hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-700">
                    <flux:icon name="user-plus" class="size-4" /> {{ __('Add new customer “:q”', ['q' => $search]) }}
                </button>
            </div>
        @endif
    @endif
</div>
