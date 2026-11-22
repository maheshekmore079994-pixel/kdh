<div>
    <x-page-header :title="__('Website enquiries')" :subtitle="__('Messages sent through the public contact form.')">
        @if($newCount)<flux:badge color="amber">{{ __(':n new', ['n' => $newCount]) }}</flux:badge>@endif
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Name, phone, message')" size="sm" class="w-56" />
        <flux:select wire:model.live="status" size="sm" class="min-w-36">
            <flux:select.option value="new">{{ __('New') }}</flux:select.option>
            <flux:select.option value="contacted">{{ __('Contacted') }}</flux:select.option>
            <flux:select.option value="closed">{{ __('Closed') }}</flux:select.option>
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
        </flux:select>
    </x-page-header>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$enquiries">
            <flux:table.columns>
                <flux:table.column>{{ __('Received') }}</flux:table.column>
                <flux:table.column>{{ __('From') }}</flux:table.column>
                <flux:table.column>{{ __('Service') }}</flux:table.column>
                <flux:table.column>{{ __('Message') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($enquiries as $e)
                    <flux:table.row :key="$e->id">
                        <flux:table.cell class="whitespace-nowrap">{{ $e->created_at->format('d M Y, h:i A') }}</flux:table.cell>
                        <flux:table.cell><div class="font-medium">{{ $e->name }}</div><div class="text-xs text-zinc-500"><a href="tel:{{ $e->phone }}">{{ $e->phone }}</a>@if($e->email) · {{ $e->email }}@endif</div></flux:table.cell>
                        <flux:table.cell>{{ $e->service_interest ?: '—' }}</flux:table.cell>
                        <flux:table.cell class="max-w-md"><div class="truncate">{{ $e->message }}</div></flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$e->status->color()">{{ $e->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-end"><flux:button wire:click="view({{ $e->id }})" size="xs" variant="ghost" icon="eye">{{ __('Open') }}</flux:button></flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="6"><x-empty-state icon="inbox" :title="__('No enquiries')" class="border-0" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="enquiry-view" class="w-full max-w-lg">
        @if($viewing)
            <div class="space-y-4">
                <flux:heading size="lg">{{ $viewing->name }}</flux:heading>
                <flux:text class="text-sm">{{ $viewing->created_at->format('d M Y, h:i A') }} · <a href="tel:{{ $viewing->phone }}" class="underline">{{ $viewing->phone }}</a>@if($viewing->email) · <a href="mailto:{{ $viewing->email }}" class="underline">{{ $viewing->email }}</a>@endif @if($viewing->service_interest)· {{ $viewing->service_interest }}@endif</flux:text>
                <div class="rounded-lg bg-zinc-50 p-3 text-sm whitespace-pre-line dark:bg-zinc-900">{{ $viewing->message }}</div>
                <div class="flex gap-2">
                    <flux:button :href="'https://wa.me/91'.preg_replace('/\D/', '', ltrim($viewing->phone, '+91'))" target="_blank" size="sm" icon="chat-bubble-left-right">WhatsApp</flux:button>
                    <flux:button :href="'tel:'.$viewing->phone" size="sm" icon="phone">{{ __('Call') }}</flux:button>
                </div>
                <flux:textarea wire:model="note" :label="__('Internal note')" rows="2" />
                <div class="flex justify-end gap-2">
                    <flux:button wire:click="setStatus('contacted')" size="sm">{{ __('Mark contacted') }}</flux:button>
                    <flux:button wire:click="setStatus('closed')" size="sm" variant="primary">{{ __('Close') }}</flux:button>
                </div>
                @if($viewing->handler)<flux:text class="text-xs">{{ __('Last handled by :u on :d', ['u' => $viewing->handler->name, 'd' => $viewing->handled_at?->format('d M Y, h:i A')]) }}</flux:text>@endif
            </div>
        @endif
    </flux:modal>
</div>
