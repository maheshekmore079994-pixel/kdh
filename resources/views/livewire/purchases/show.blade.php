<div>
    <x-page-header :title="$purchase->purchase_number" :subtitle="$purchase->purchase_date->format('d M Y').' · '.$purchase->supplier?->name.($purchase->supplier_invoice_no ? ' · '.__('Bill').' '.$purchase->supplier_invoice_no : '')">
        <flux:badge :color="$purchase->status->color()">{{ $purchase->status->label() }}</flux:badge>
        <flux:badge :color="$purchase->payment_status->color()">{{ $purchase->payment_status->label() }}</flux:badge>
        @if($purchase->status === \App\Enums\PurchaseStatus::Draft)
            <flux:button wire:click="post" wire:confirm="{{ __('Post this purchase and add items to stock?') }}" icon="check" variant="primary" size="sm">{{ __('Post to stock') }}</flux:button>
        @endif
        @if($purchase->status !== \App\Enums\PurchaseStatus::Cancelled)
            @if(bccomp($purchase->balance_due, '0', 2) > 0)<flux:modal.trigger name="supplier-payment"><flux:button icon="currency-rupee" variant="primary" size="sm">{{ __('Pay supplier') }}</flux:button></flux:modal.trigger>@endif
            <flux:modal.trigger name="cancel-purchase"><flux:button icon="x-circle" variant="danger" size="sm">{{ __('Cancel') }}</flux:button></flux:modal.trigger>
        @endif
        <flux:button :href="route('purchases.index')" variant="ghost" size="sm" wire:navigate>{{ __('All purchases') }}</flux:button>
    </x-page-header>

    @if($purchase->status === \App\Enums\PurchaseStatus::Cancelled)
        <flux:callout variant="danger" icon="x-circle" class="mb-4"><flux:callout.text>{{ __('Cancelled on :d. Reason: :r', ['d' => $purchase->cancelled_at?->format('d M Y, h:i A'), 'r' => $purchase->cancel_reason]) }}</flux:callout.text></flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900"><tr><th class="px-4 py-2 text-start">{{ __('Product') }}</th><th class="px-4 py-2 text-end">{{ __('Qty') }}</th><th class="px-4 py-2 text-end">{{ __('Unit cost') }}</th><th class="px-4 py-2 text-end">{{ __('Amount') }}</th></tr></thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($purchase->items as $item)
                            <tr><td class="px-4 py-2"><a href="{{ route('products.show', $item->product) }}" class="hover:underline" wire:navigate>{{ $item->product?->name }}</a></td><td class="px-4 py-2 text-end tabular-nums">{{ qty($item->quantity) }} {{ $item->product?->unit }}</td><td class="px-4 py-2 text-end tabular-nums">{{ money($item->unit_cost) }}</td><td class="px-4 py-2 text-end tabular-nums font-medium">{{ money($item->line_total) }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="px-4 pt-3 text-end text-zinc-500">{{ __('Items') }}</td><td class="px-4 pt-3 text-end tabular-nums">{{ money($purchase->subtotal) }}</td></tr>
                        @if(bccomp($purchase->other_charges, '0', 2) > 0)<tr><td colspan="3" class="px-4 text-end text-zinc-500">{{ __('Other charges') }}</td><td class="px-4 text-end tabular-nums">{{ money($purchase->other_charges) }}</td></tr>@endif
                        <tr class="font-semibold"><td colspan="3" class="px-4 py-1 text-end">{{ __('Total') }}</td><td class="px-4 py-1 text-end tabular-nums">{{ money($purchase->total) }}</td></tr>
                        <tr><td colspan="3" class="px-4 text-end text-zinc-500">{{ __('Paid') }}</td><td class="px-4 text-end tabular-nums text-emerald-600">{{ money($purchase->paid_amount) }}</td></tr>
                        <tr><td colspan="3" class="px-4 pb-3 text-end text-zinc-500">{{ __('Balance due') }}</td><td class="px-4 pb-3 text-end tabular-nums font-semibold {{ bccomp($purchase->balance_due, '0', 2) > 0 ? 'text-amber-600' : '' }}">{{ money($purchase->balance_due) }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">{{ __('Supplier payments') }}</flux:heading>
                @if($purchase->payments->isEmpty())
                    <flux:text>{{ __('No payments yet.') }}</flux:text>
                @else
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($purchase->payments as $p)
                                <tr class="{{ $p->reversed_at ? 'text-zinc-400 line-through' : '' }}">
                                    <td class="py-2">{{ $p->paid_at->format('d M Y, h:i A') }}</td>
                                    <td class="py-2">{{ $p->method?->name }}@if($p->reference) <span class="text-xs">({{ $p->reference }})</span>@endif</td>
                                    <td class="py-2">{{ $p->payer?->name }}</td>
                                    <td class="py-2 text-end tabular-nums">{{ money($p->amount) }}</td>
                                    <td class="py-2 text-end">@if($p->reversed_at)<flux:tooltip :content="$p->reversal_reason"><flux:badge size="sm" color="zinc">{{ __('Reversed') }}</flux:badge></flux:tooltip>@else<flux:button wire:click="startReverse({{ $p->id }})" size="xs" variant="ghost" icon="arrow-uturn-left">{{ __('Reverse') }}</flux:button>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">{{ __('Supplier bill / documents') }}</flux:heading>
                <livewire:documents.manager :model="$purchase" :key="'docs-purchase-'.$purchase->id" />
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 text-sm self-start dark:border-zinc-700 dark:bg-zinc-800">
            <dl class="space-y-1">
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Supplier') }}</dt><dd>{{ $purchase->supplier?->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Recorded by') }}</dt><dd>{{ $purchase->creator?->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Posted at') }}</dt><dd>{{ $purchase->posted_at?->format('d M Y, h:i A') ?? '—' }}</dd></div>
            </dl>
            @if($purchase->notes)<p class="mt-3 border-t border-zinc-100 pt-2 dark:border-zinc-700">{{ $purchase->notes }}</p>@endif
        </div>
    </div>

    <flux:modal name="supplier-payment" class="w-full max-w-md">
        <form wire:submit="recordPayment" class="space-y-4">
            <flux:heading size="lg">{{ __('Pay supplier') }}</flux:heading>
            <flux:input wire:model="payAmount" type="number" step="0.01" min="0.01" :label="__('Amount (₹)')" required autofocus />
            <flux:select wire:model="payMethodId" :label="__('Method')">@foreach($methods as $m)<flux:select.option :value="$m->id">{{ $m->name }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="payReference" :label="__('Reference (optional)')" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ __('Confirm') }}</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="reverse-supplier-payment" class="w-full max-w-md">
        <form wire:submit="reversePayment" class="space-y-4">
            <flux:heading size="lg">{{ __('Reverse supplier payment') }}</flux:heading>
            <flux:textarea wire:model="reverseReason" :label="__('Reason')" rows="2" required autofocus />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Keep') }}</flux:button></flux:modal.close><flux:button type="submit" variant="danger">{{ __('Reverse') }}</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="cancel-purchase" class="w-full max-w-md">
        <form wire:submit="cancel" class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel purchase :n', ['n' => $purchase->purchase_number]) }}</flux:heading>
            <flux:text>{{ __('Stock added by this purchase is removed again. Reverse supplier payments first.') }}</flux:text>
            <flux:textarea wire:model="cancelReason" :label="__('Reason')" rows="2" required autofocus />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Keep') }}</flux:button></flux:modal.close><flux:button type="submit" variant="danger">{{ __('Cancel purchase') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
