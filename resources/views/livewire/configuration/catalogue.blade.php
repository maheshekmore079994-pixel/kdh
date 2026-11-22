<div>
    <x-page-header :title="__('Services & prices')" :subtitle="__('Every price here is set by you. Services without a price must be priced at billing time.')">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search services')" size="sm" class="w-56" />
        <flux:button wire:click="createCategory" icon="plus" size="sm">{{ __('Category') }}</flux:button>
        <flux:button wire:click="createService" icon="plus" size="sm" variant="primary">{{ __('Service') }}</flux:button>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-4">
        {{-- Categories --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-2 self-start dark:border-zinc-700 dark:bg-zinc-800">
            <flux:navlist>
                @foreach($categories as $cat)
                    <flux:navlist.item wire:click="$set('categoryId', {{ $cat->id }})" :current="!$search && $categoryId === $cat->id" class="cursor-pointer">
                        <span class="flex w-full items-center justify-between gap-2">
                            <span class="truncate {{ $cat->is_active ? '' : 'line-through text-zinc-400' }}">{{ $cat->name }}</span>
                            <flux:badge size="sm">{{ $cat->services_count }}</flux:badge>
                        </span>
                    </flux:navlist.item>
                @endforeach
            </flux:navlist>
            @if($currentCategory)
                <div class="mt-2 border-t border-zinc-100 px-2 pt-2 dark:border-zinc-700">
                    <flux:button wire:click="editCategory({{ $currentCategory->id }})" size="xs" variant="ghost" icon="pencil-square">{{ __('Edit category') }}</flux:button>
                </div>
            @endif
        </div>

        {{-- Services --}}
        <div class="space-y-3 lg:col-span-3">
            @if($currentCategory && !$search)
                <div class="flex items-center gap-2">
                    <flux:heading size="lg">{{ $currentCategory->name }}</flux:heading>
                    @unless($currentCategory->is_active)<flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge>@endunless
                    @unless($currentCategory->show_on_website)<flux:badge size="sm" color="zinc">{{ __('Hidden on website') }}</flux:badge>@endunless
                </div>
            @endif

            @forelse($services as $service)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-zinc-900 dark:text-white {{ $service->is_active ? '' : 'line-through text-zinc-400' }}">{{ $service->name }}</span>
                                @if($service->code)<flux:badge size="sm" class="font-mono">{{ $service->code }}</flux:badge>@endif
                                @unless($service->is_active)<flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge>@endunless
                            </div>
                            <div class="text-sm text-zinc-500">
                                {{ __('Base price:') }}
                                @if($service->default_price !== null)
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ money($service->default_price) }} / {{ $service->unit }}</span>
                                @else
                                    <span class="text-amber-600">{{ __('not set — entered at billing') }}</span>
                                @endif
                                @if($service->description) · {{ $service->description }}@endif
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <flux:button wire:click="createVariant({{ $service->id }})" size="xs" variant="ghost" icon="plus">{{ __('Price variant') }}</flux:button>
                            <flux:button wire:click="editService({{ $service->id }})" size="xs" variant="ghost" icon="pencil-square">{{ __('Edit') }}</flux:button>
                        </div>
                    </div>

                    @if($service->variants->isNotEmpty())
                        <div class="mt-3 overflow-hidden rounded-lg border border-zinc-100 dark:border-zinc-700">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900">
                                    <tr><th class="px-3 py-1.5 text-start">{{ __('Variant') }}</th><th class="px-3 py-1.5 text-end">{{ __('Unit price') }}</th><th class="px-3 py-1.5 text-end">{{ __('Min qty') }}</th><th class="px-3 py-1.5"></th></tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                    @foreach($service->variants as $variant)
                                        <tr class="{{ $variant->is_active ? '' : 'text-zinc-400' }}">
                                            <td class="px-3 py-1.5">{{ $variant->name }} @unless($variant->is_active)<flux:badge size="sm" color="zinc">{{ __('Inactive') }}</flux:badge>@endunless</td>
                                            <td class="px-3 py-1.5 text-end tabular-nums">{{ money($variant->unit_price) }}</td>
                                            <td class="px-3 py-1.5 text-end tabular-nums">{{ qty($variant->min_quantity) }}</td>
                                            <td class="px-3 py-1.5 text-end whitespace-nowrap">
                                                <flux:button wire:click="editVariant({{ $variant->id }})" size="xs" variant="ghost" icon="pencil-square" />
                                                <flux:button wire:click="deleteVariant({{ $variant->id }})" wire:confirm="{{ __('Remove this price variant?') }}" size="xs" variant="ghost" icon="trash" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @empty
                <x-empty-state icon="clipboard-document-list" :title="$search ? __('No services match') : __('No services in this category')">
                    <flux:button wire:click="createService" icon="plus" size="sm" variant="primary">{{ __('Add service') }}</flux:button>
                </x-empty-state>
            @endforelse
        </div>
    </div>

    {{-- Category modal --}}
    <flux:modal name="category-form" class="w-full max-w-md">
        <form wire:submit="saveCategory" class="space-y-5">
            <flux:heading size="lg">{{ $catEditingId ? __('Edit category') : __('New category') }}</flux:heading>
            <flux:input wire:model="catName" :label="__('Name')" required autofocus />
            <flux:textarea wire:model="catDescription" :label="__('Description (shown on website)')" rows="2" />
            <flux:switch wire:model="catActive" :label="__('Active')" />
            <flux:switch wire:model="catOnWebsite" :label="__('Show on website')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Service modal --}}
    <flux:modal name="service-form" class="w-full max-w-lg">
        <form wire:submit="saveService" class="space-y-5">
            <flux:heading size="lg">{{ $svcEditingId ? __('Edit service') : __('New service') }}</flux:heading>
            <flux:select wire:model="svcCategoryId" :label="__('Category')">
                @foreach($categories as $cat)
                    <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="svcName" :label="__('Service name')" required autofocus />
            <div class="grid grid-cols-3 gap-4">
                <flux:input wire:model="svcCode" :label="__('Code (optional)')" placeholder="BW-A4" />
                <flux:select wire:model="svcUnit" :label="__('Unit')">
                    @foreach($units as $u)
                        <flux:select.option :value="$u->abbreviation">{{ $u->name }} ({{ $u->abbreviation }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="svcPrice" :label="__('Base price (₹)')" type="number" step="0.01" min="0" :placeholder="__('blank = ask')" />
            </div>
            <flux:textarea wire:model="svcDescription" :label="__('Description')" rows="2" />
            <div class="grid grid-cols-2 gap-4">
                <flux:switch wire:model="svcActive" :label="__('Active')" />
                <flux:switch wire:model="svcOnWebsite" :label="__('Show on website')" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Variant modal --}}
    <flux:modal name="variant-form" class="w-full max-w-md">
        <form wire:submit="saveVariant" class="space-y-5">
            <flux:heading size="lg">{{ $varEditingId ? __('Edit price variant') : __('New price variant') }}</flux:heading>
            <flux:text class="text-sm">{{ __('Use variants for paper size, side, colour or quantity slabs, e.g. “A4 single side”, “A3 colour”, “100+ copies”.') }}</flux:text>
            <flux:input wire:model="varName" :label="__('Variant name')" required autofocus />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="varPrice" :label="__('Unit price (₹)')" type="number" step="0.01" min="0" required />
                <flux:input wire:model="varMinQty" :label="__('Minimum quantity')" type="number" step="0.001" min="0.001" :description="__('For quantity slabs')" />
            </div>
            <flux:switch wire:model="varActive" :label="__('Active')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
