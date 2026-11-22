<div class="grid gap-6 xl:grid-cols-5" x-data>
    {{-- Catalogue --}}
    <div class="xl:col-span-2">
        <x-page-header :title="__('New bill')" :subtitle="__('Tap an item to add it. Prices can be edited on the bill.')" class="mb-4" />
        <flux:input wire:model.live.debounce.200ms="search" icon="magnifying-glass" :placeholder="__('Search services or products')" autofocus autocomplete="off" />

        <div class="mt-3 max-h-[70vh] space-y-3 overflow-y-auto pr-1">
            @if($services->isNotEmpty())
                <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('Services') }}</div>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($services as $service)
                        @if($service->variants->isEmpty())
                            <button type="button" wire:click="addService({{ $service->id }})" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-start text-sm hover:border-[#D59A2F] hover:bg-amber-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $service->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $service->default_price !== null ? money($service->default_price).' / '.$service->unit : __('price at billing') }}</div>
                            </button>
                        @else
                            @foreach($service->variants as $variant)
                                <button type="button" wire:click="addService({{ $service->id }}, {{ $variant->id }})" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-start text-sm hover:border-[#D59A2F] hover:bg-amber-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $service->name }} <span class="font-normal text-zinc-500">– {{ $variant->name }}</span></div>
                                    <div class="text-xs text-zinc-500">{{ money($variant->unit_price) }} / {{ $service->unit }}@if(bccomp($variant->min_quantity, '1', 3) > 0) · min {{ qty($variant->min_quantity) }}@endif</div>
                                </button>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            @endif

            @if($products->isNotEmpty())
                <div class="pt-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ __('Products') }}</div>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach($products as $product)
                        <button type="button" wire:click="addProduct({{ $product->id }})" class="rounded-lg border border-zinc-200 bg-white px-3 py-2 text-start text-sm hover:border-[#D59A2F] hover:bg-amber-50 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $product->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $product->selling_price !== null ? money($product->selling_price) : __('price at billing') }} · {{ __('stock') }} {{ qty($product->current_stock) }} {{ $product->unit }}</div>
                        </button>
                    @endforeach
                </div>
            @endif

            @if($services->isEmpty() && $products->isEmpty())
                <x-empty-state icon="magnifying-glass" :title="__('Nothing matches')" :description="__('Add a custom line instead, or set up services under Configuration → Services & prices.')" />
            @endif

            <flux:button wire:click="addCustomLine" icon="plus" size="sm" variant="ghost">{{ __('Add custom line') }}</flux:button>
        </div>
    </div>

    {{-- Bill --}}
    <div class="xl:col-span-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4">
                <flux:label>{{ __('Customer') }}</flux:label>
                <livewire:customers.picker wire:model="customerId" />
            </div>

            @error('lines')<flux:callout variant="danger" icon="exclamation-triangle" class="mb-3"><flux:callout.text>{{ $message }}</flux:callout.text></flux:callout>@enderror

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-zinc-500">
                        <tr>
                            <th class="pb-2 text-start">{{ __('Item') }}</th>
                            <th class="pb-2 w-20 text-end">{{ __('Qty') }}</th>
                            <th class="pb-2 w-28 text-end">{{ __('Price') }}</th>
                            <th class="pb-2 w-24 text-end">{{ __('Disc.') }}</th>
                            <th class="pb-2 w-28 text-end">{{ __('Amount') }}</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @forelse($lines as $i => $line)
                            <tr wire:key="line-{{ $i }}">
                                <td class="py-2 pr-2">
                                    <flux:input wire:model="lines.{{ $i }}.description" size="sm" :placeholder="__('Description')" />
                                    @error("lines.$i.description")<div class="text-xs text-red-600">{{ $message }}</div>@enderror
                                    @if($line['type'] === 'product')<span class="text-[10px] uppercase text-zinc-400">{{ __('stock item') }}</span>@endif
                                </td>
                                <td class="py-2 pr-2"><flux:input wire:model.live.debounce.300ms="lines.{{ $i }}.quantity" size="sm" type="number" step="any" min="0" class="text-end" /></td>
                                <td class="py-2 pr-2">
                                    <flux:input wire:model.live.debounce.300ms="lines.{{ $i }}.unit_price" size="sm" type="number" step="0.01" min="0" class="text-end" :autofocus="$line['unit_price'] === ''" />
                                    @error("lines.$i.unit_price")<div class="text-xs text-red-600">{{ __('Required') }}</div>@enderror
                                </td>
                                <td class="py-2 pr-2"><flux:input wire:model.live.debounce.300ms="lines.{{ $i }}.discount_amount" size="sm" type="number" step="0.01" min="0" class="text-end" placeholder="0" /></td>
                                <td class="py-2 text-end tabular-nums font-medium">{{ money(\App\Support\Money::sub(\App\Support\Money::mul((float)($line['quantity'] ?: 0), (float)($line['unit_price'] ?: 0)), (float)($line['discount_amount'] ?: 0))) }}</td>
                                <td class="py-2 text-end"><flux:button wire:click="removeLine({{ $i }})" size="xs" variant="ghost" icon="x-mark" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-zinc-500">{{ __('No items yet. Pick a service or product on the left.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div class="space-y-3">
                    <flux:input wire:model.live.debounce.300ms="discount" type="number" step="0.01" min="0" :label="__('Bill discount (₹)')" size="sm" />
                    @if($taxEnabled)
                        <flux:switch wire:model.live="applyTax" :label="__('Apply :label @ :rate%', ['label' => setting('tax.label'), 'rate' => setting('tax.rate')])" />
                    @endif
                    <flux:textarea wire:model="notes" :label="__('Notes (printed on bill)')" rows="2" size="sm" />
                </div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-zinc-500">{{ __('Subtotal') }}</span><span class="tabular-nums">{{ money($totals['subtotal']) }}</span></div>
                    @if(bccomp($totals['discount'], '0', 2) > 0)<div class="flex justify-between"><span class="text-zinc-500">{{ __('Discount') }}</span><span class="tabular-nums">−{{ money($totals['discount']) }}</span></div>@endif
                    @if($totals['tax_rate'] !== null)<div class="flex justify-between"><span class="text-zinc-500">{{ setting('tax.label') }} {{ $totals['tax_rate'] }}%</span><span class="tabular-nums">{{ money($totals['tax']) }}</span></div>@endif
                    <div class="flex justify-between border-t border-zinc-200 pt-2 text-lg font-semibold dark:border-zinc-700"><span>{{ __('Total') }}</span><span class="tabular-nums">{{ money($totals['total']) }}</span></div>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="flex items-end gap-2">
                        <flux:input wire:model.live.debounce.300ms="paymentAmount" type="number" step="0.01" min="0" :label="__('Received now (₹)')" size="sm" />
                        <flux:button wire:click="payFull" size="sm" variant="ghost">{{ __('Full') }}</flux:button>
                    </div>
                    <flux:select wire:model="paymentMethodId" :label="__('Method')" size="sm">
                        @foreach($methods as $m)
                            <flux:select.option :value="$m->id">{{ $m->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="paymentReference" :label="__('Reference (UPI / txn id)')" size="sm" />
                </div>
                @php($balance = \App\Support\Money::sub($totals['total'], (float)($paymentAmount ?: 0)))
                <div class="mt-2 flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ __('Balance due') }}</span>
                    <span class="tabular-nums font-semibold {{ bccomp($balance, '0', 2) > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ money(\App\Support\Money::max($balance, '0')) }}</span>
                </div>
                @if(bccomp($balance, '0', 2) > 0 && !$customerId)
                    <flux:text class="mt-1 text-xs text-amber-700">{{ __('A customer is required to leave a balance due.') }}</flux:text>
                @endif
                @error('paymentAmount')<div class="text-xs text-red-600">{{ $message }}</div>@enderror
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <flux:button :href="route('dashboard')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="save" variant="primary" icon="check" wire:loading.attr="disabled">{{ __('Save & print') }}</flux:button>
            </div>
        </div>
    </div>
</div>
