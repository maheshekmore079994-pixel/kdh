<div>
    @if($autoPrint)
        <iframe src="{{ route('print.invoice', [$invoice, $autoPrint, 'auto' => 1]) }}" class="hidden" title="print"></iframe>
    @endif

    <x-page-header :title="$invoice->invoice_number" :subtitle="$invoice->invoice_date->format('d M Y').' · '.($invoice->customer?->name ?? __('Walk-in customer')).($invoice->customer ? ' · '.$invoice->customer->mobile : '')">
        <flux:badge :color="$invoice->status->color()">{{ $invoice->status->label() }}</flux:badge>
        <flux:dropdown>
            <flux:button icon="printer" size="sm">{{ __('Print') }}</flux:button>
            <flux:menu>
                <flux:menu.item :href="route('print.invoice', [$invoice, 'receipt'])" target="_blank" icon="printer">{{ __('Receipt (thermal)') }}</flux:menu.item>
                <flux:menu.item :href="route('print.invoice', [$invoice, 'a4'])" target="_blank" icon="document-text">{{ __('A4 invoice') }}</flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item :href="route('pdf.invoice', [$invoice, 'a4'])" icon="arrow-down-tray">{{ __('Download PDF (A4)') }}</flux:menu.item>
                <flux:menu.item :href="route('pdf.invoice', [$invoice, 'receipt'])" icon="arrow-down-tray">{{ __('Download PDF (receipt)') }}</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
        @unless($invoice->isCancelled())
            @if(bccomp($invoice->balance_due, '0', 2) > 0)
                <flux:modal.trigger name="payment-form"><flux:button icon="currency-rupee" variant="primary" size="sm">{{ __('Record payment') }}</flux:button></flux:modal.trigger>
            @endif
            <flux:modal.trigger name="cancel-form"><flux:button icon="x-circle" variant="danger" size="sm">{{ __('Cancel invoice') }}</flux:button></flux:modal.trigger>
        @endunless
        <flux:button :href="route('invoices.index')" variant="ghost" size="sm" wire:navigate>{{ __('All invoices') }}</flux:button>
    </x-page-header>

    @if($invoice->isCancelled())
        <flux:callout variant="danger" icon="x-circle" class="mb-4">
            <flux:callout.heading>{{ __('Cancelled on :date by :user', ['date' => $invoice->cancelled_at->format('d M Y, h:i A'), 'user' => $invoice->canceller?->name]) }}</flux:callout.heading>
            <flux:callout.text>{{ $invoice->cancel_reason }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900">
                        <tr><th class="px-4 py-2 text-start">{{ __('Item') }}</th><th class="px-4 py-2 text-end">{{ __('Qty') }}</th><th class="px-4 py-2 text-end">{{ __('Price') }}</th><th class="px-4 py-2 text-end">{{ __('Disc.') }}</th><th class="px-4 py-2 text-end">{{ __('Amount') }}</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-4 py-2">{{ $item->description }}@if($item->item_type->value === 'product')<span class="ms-1 text-[10px] uppercase text-zinc-400">{{ __('stock') }}</span>@endif</td>
                                <td class="px-4 py-2 text-end tabular-nums">{{ qty($item->quantity) }} {{ $item->unit }}</td>
                                <td class="px-4 py-2 text-end tabular-nums">{{ money($item->unit_price) }}</td>
                                <td class="px-4 py-2 text-end tabular-nums">{{ bccomp($item->discount_amount, '0', 2) > 0 ? money($item->discount_amount) : '—' }}</td>
                                <td class="px-4 py-2 text-end tabular-nums font-medium">{{ money($item->line_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="text-sm">
                        <tr><td colspan="4" class="px-4 pt-3 text-end text-zinc-500">{{ __('Subtotal') }}</td><td class="px-4 pt-3 text-end tabular-nums">{{ money($invoice->subtotal) }}</td></tr>
                        @if(bccomp($invoice->discount_amount, '0', 2) > 0)<tr><td colspan="4" class="px-4 text-end text-zinc-500">{{ __('Discount') }}</td><td class="px-4 text-end tabular-nums">−{{ money($invoice->discount_amount) }}</td></tr>@endif
                        @if($invoice->tax_rate !== null)<tr><td colspan="4" class="px-4 text-end text-zinc-500">{{ setting('tax.label') }} {{ $invoice->tax_rate }}%</td><td class="px-4 text-end tabular-nums">{{ money($invoice->tax_amount) }}</td></tr>@endif
                        <tr class="text-base font-semibold"><td colspan="4" class="px-4 py-2 text-end">{{ __('Total') }}</td><td class="px-4 py-2 text-end tabular-nums">{{ money($invoice->total) }}</td></tr>
                        <tr><td colspan="4" class="px-4 text-end text-zinc-500">{{ __('Paid') }}</td><td class="px-4 text-end tabular-nums text-emerald-600">{{ money($invoice->paid_amount) }}</td></tr>
                        <tr><td colspan="4" class="px-4 pb-3 text-end text-zinc-500">{{ __('Balance due') }}</td><td class="px-4 pb-3 text-end tabular-nums font-semibold {{ bccomp($invoice->balance_due, '0', 2) > 0 ? 'text-amber-600' : '' }}">{{ money($invoice->balance_due) }}</td></tr>
                    </tfoot>
                </table>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">{{ __('Payments') }}</flux:heading>
                @if($invoice->payments->isEmpty())
                    <flux:text>{{ __('No payments recorded.') }}</flux:text>
                @else
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-zinc-500"><tr><th class="py-1 text-start">{{ __('Receipt') }}</th><th class="py-1 text-start">{{ __('When') }}</th><th class="py-1 text-start">{{ __('Method') }}</th><th class="py-1 text-start">{{ __('By') }}</th><th class="py-1 text-end">{{ __('Amount') }}</th><th></th></tr></thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($invoice->payments as $p)
                                <tr class="{{ $p->isReversed() ? 'text-zinc-400 line-through' : '' }}">
                                    <td class="py-2 font-medium">{{ $p->payment_number }}</td>
                                    <td class="py-2">{{ $p->paid_at->format('d M Y, h:i A') }}</td>
                                    <td class="py-2">{{ $p->method?->name }}@if($p->reference) <span class="text-xs text-zinc-500">({{ $p->reference }})</span>@endif</td>
                                    <td class="py-2">{{ $p->receiver?->name }}</td>
                                    <td class="py-2 text-end tabular-nums">{{ money($p->amount) }}</td>
                                    <td class="py-2 text-end">
                                        @if($p->isReversed())
                                            <flux:tooltip :content="$p->reversal_reason"><flux:badge size="sm" color="zinc">{{ __('Reversed') }}</flux:badge></flux:tooltip>
                                        @else
                                            <flux:button :href="route('print.payment', $p)" target="_blank" size="xs" variant="ghost" icon="printer" />
                                            <flux:button wire:click="startReverse({{ $p->id }})" size="xs" variant="ghost" icon="arrow-uturn-left">{{ __('Reverse') }}</flux:button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        <div class="space-y-4 text-sm">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-2">{{ __('Details') }}</flux:heading>
                <dl class="space-y-1">
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Customer') }}</dt><dd>@if($invoice->customer)<a href="{{ route('customers.show', $invoice->customer) }}" class="hover:underline" wire:navigate>{{ $invoice->customer->name }}</a>@else{{ __('Walk-in') }}@endif</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Created by') }}</dt><dd>{{ $invoice->creator?->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Created at') }}</dt><dd>{{ $invoice->created_at->format('d M Y, h:i A') }}</dd></div>
                    @if($invoice->job)<div class="flex justify-between"><dt class="text-zinc-500">{{ __('Job') }}</dt><dd><a href="{{ route('jobs.show', $invoice->job) }}" class="hover:underline" wire:navigate>{{ $invoice->job->job_number }}</a></dd></div>@endif
                </dl>
                @if($invoice->notes)<p class="mt-3 border-t border-zinc-100 pt-2 text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">{{ $invoice->notes }}</p>@endif
            </div>
        </div>
    </div>

    <flux:modal name="payment-form" class="w-full max-w-md">
        <form wire:submit="recordPayment" class="space-y-4">
            <flux:heading size="lg">{{ __('Record payment') }}</flux:heading>
            <flux:text>{{ __('Balance due: :due', ['due' => money($invoice->balance_due)]) }}</flux:text>
            <flux:input wire:model="payAmount" type="number" step="0.01" min="0.01" :label="__('Amount received (₹)')" required autofocus />
            <flux:select wire:model="payMethodId" :label="__('Payment method')">
                @foreach($methods as $m)<flux:select.option :value="$m->id">{{ $m->name }}</flux:select.option>@endforeach
            </flux:select>
            <flux:input wire:model="payReference" :label="__('Reference (UPI / transaction id, optional)')" />
            <flux:input wire:model="payNotes" :label="__('Notes (optional)')" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Confirm payment') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="reverse-form" class="w-full max-w-md">
        <form wire:submit="reversePayment" class="space-y-4">
            <flux:heading size="lg">{{ __('Reverse payment') }}</flux:heading>
            <flux:text>{{ __('The payment stays on record marked as reversed. The invoice balance is recalculated.') }}</flux:text>
            <flux:textarea wire:model="reverseReason" :label="__('Reason')" rows="2" required autofocus />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Keep payment') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">{{ __('Reverse') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="cancel-form" class="w-full max-w-md">
        <form wire:submit="cancelInvoice" class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel invoice :n', ['n' => $invoice->invoice_number]) }}</flux:heading>
            <flux:text>{{ __('Stock items are returned to inventory and the invoice is excluded from sales reports. All payments must be reversed first.') }}</flux:text>
            <flux:textarea wire:model="cancelReason" :label="__('Reason')" rows="2" required autofocus />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">{{ __('Keep invoice') }}</flux:button></flux:modal.close>
                <flux:button type="submit" variant="danger">{{ __('Cancel invoice') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
