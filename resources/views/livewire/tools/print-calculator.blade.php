<div>
    <x-page-header :title="__('Print cost calculator')" :subtitle="__('Estimates only. Nothing here is recorded in accounts. Costs come from profiles you define — no default rates are assumed.')">
        @can('settings')<flux:button wire:click="createProfile" icon="plus" size="sm">{{ __('New cost profile') }}</flux:button>@endcan
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-800">
            @if($profiles->isEmpty())
                <x-empty-state icon="calculator" :title="__('No cost profiles yet')" :description="__('Create a profile with your paper, ink/toner and maintenance costs (e.g. “A4 B/W laser”, “A4 colour inkjet”).')">
                    @can('settings')<flux:button wire:click="createProfile" variant="primary" size="sm">{{ __('Create profile') }}</flux:button>@endcan
                </x-empty-state>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model.live="profileId" :label="__('Cost profile')">
                        @foreach($profiles as $p)<flux:select.option :value="$p->id">{{ $p->name }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model.live="sides" :label="__('Printing')">
                        <flux:select.option value="1">{{ __('Single side') }}</flux:select.option>
                        <flux:select.option value="2">{{ __('Double side') }}</flux:select.option>
                    </flux:select>
                    <flux:input wire:model.live.debounce.300ms="pages" type="number" min="0" :label="__('Pages per copy')" />
                    <flux:input wire:model.live.debounce.300ms="copies" type="number" min="0" :label="__('Copies')" />
                    <flux:input wire:model.live.debounce.300ms="charge" type="number" step="0.01" min="0" :label="__('Charge to customer (₹, optional)')" />
                </div>

                @if($profile)
                    <div class="rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-900">
                        <div class="flex flex-wrap gap-x-6 gap-y-1 text-zinc-600 dark:text-zinc-300">
                            <span>{{ __('Paper/sheet') }}: <b>{{ $profile->paper_cost_per_sheet !== null ? '₹'.rtrim(rtrim($profile->paper_cost_per_sheet, '0'), '.') : '—' }}</b></span>
                            <span>{{ __('Ink/toner per page') }}: <b>{{ $profile->ink_cost_per_page !== null ? '₹'.rtrim(rtrim($profile->ink_cost_per_page, '0'), '.') : '—' }}</b></span>
                            <span>{{ __('Maintenance per page') }}: <b>{{ $profile->maintenance_per_page !== null ? '₹'.rtrim(rtrim($profile->maintenance_per_page, '0'), '.') : '—' }}</b></span>
                            <span>{{ __('Other per page') }}: <b>{{ $profile->other_cost_per_page !== null ? '₹'.rtrim(rtrim($profile->other_cost_per_page, '0'), '.') : '—' }}</b></span>
                            @can('settings')<flux:button wire:click="editProfile({{ $profile->id }})" size="xs" variant="ghost" icon="pencil-square">{{ __('Edit') }}</flux:button><flux:button wire:click="deleteProfile({{ $profile->id }})" wire:confirm="{{ __('Delete this profile?') }}" size="xs" variant="ghost" icon="trash" />@endcan
                        </div>
                        @if($profile->notes)<div class="mt-1 text-xs text-zinc-500">{{ $profile->notes }}</div>@endif
                    </div>
                @endif
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 self-start dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-3">{{ __('Estimate') }}</flux:heading>
            @if($estimate)
                <dl class="space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Total pages') }}</dt><dd>{{ $estimate['total_pages'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Sheets used') }}</dt><dd>{{ $estimate['sheets'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Paper cost') }}</dt><dd class="tabular-nums">{{ money($estimate['paper_cost']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Ink, maintenance, other') }}</dt><dd class="tabular-nums">{{ money($estimate['page_costs']) }}</dd></div>
                    <div class="flex justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700"><dt>{{ __('Estimated cost') }}</dt><dd class="tabular-nums">{{ money($estimate['cost']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Cost per page') }}</dt><dd class="tabular-nums">{{ money($estimate['cost_per_page']) }}</dd></div>
                    @if($estimate['charge'] !== null)
                        <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Charge') }}</dt><dd class="tabular-nums">{{ money($estimate['charge']) }}</dd></div>
                        <div class="flex justify-between font-semibold {{ bccomp($estimate['margin'], '0', 2) >= 0 ? 'text-emerald-600' : 'text-red-600' }}"><dt>{{ __('Margin') }}</dt><dd class="tabular-nums">{{ money($estimate['margin']) }} @if($estimate['margin_pct'] !== null)({{ $estimate['margin_pct'] }}%)@endif</dd></div>
                    @endif
                </dl>
            @else
                <flux:text class="text-sm">{{ __('Select a cost profile to see the estimate.') }}</flux:text>
            @endif
            <flux:text class="mt-3 text-xs text-zinc-500">{{ __('Estimate only. Actual charges are set in Services & prices.') }}</flux:text>
        </div>
    </div>

    <flux:modal name="profile-form" class="w-full max-w-md">
        <form wire:submit="saveProfile" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit cost profile') : __('New cost profile') }}</flux:heading>
            <flux:input wire:model="name" :label="__('Name')" placeholder="A4 black & white (laser)" required autofocus />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="paper" type="number" step="0.0001" min="0" :label="__('Paper cost per sheet (₹)')" />
                <flux:input wire:model="ink" type="number" step="0.0001" min="0" :label="__('Ink / toner per page (₹)')" />
                <flux:input wire:model="maintenance" type="number" step="0.0001" min="0" :label="__('Maintenance per page (₹)')" />
                <flux:input wire:model="other" type="number" step="0.0001" min="0" :label="__('Other per page (₹)')" />
            </div>
            <flux:textarea wire:model="notes" :label="__('Notes (how you derived the figures)')" rows="2" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
