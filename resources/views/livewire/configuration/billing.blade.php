<div>
    <x-page-header :title="__('Billing & tax')" :subtitle="__('Document numbering, receipt layout and optional tax.')" />

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Document numbering') }}</flux:heading>
                <flux:text class="text-sm">{{ __('Numbers are formatted PREFIX-YEAR-NNNNN and reset every year. Changing a prefix affects new documents only.') }}</flux:text>
                <div class="grid gap-4 sm:grid-cols-5">
                    <flux:input wire:model="invoice.prefix" :label="__('Invoice')" />
                    <flux:input wire:model="invoice.job_prefix" :label="__('Job')" />
                    <flux:input wire:model="invoice.payment_prefix" :label="__('Receipt')" />
                    <flux:input wire:model="invoice.purchase_prefix" :label="__('Purchase')" />
                    <flux:input wire:model="invoice.expense_prefix" :label="__('Expense')" />
                </div>
                <div class="text-xs text-zinc-500">
                    {{ __('Next numbers:') }}
                    @foreach($sequences as $s)
                        <span class="me-3">{{ $s->prefix }}-{{ $s->year }}-{{ str_pad($s->next_number, $s->padding, '0', STR_PAD_LEFT) }}</span>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg">{{ __('Invoice & receipt layout') }}</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select wire:model="invoice.default_print" :label="__('Default print format')">
                        <flux:select.option value="receipt">{{ __('Compact receipt (thermal)') }}</flux:select.option>
                        <flux:select.option value="a4">{{ __('A4 invoice') }}</flux:select.option>
                    </flux:select>
                    <flux:select wire:model="invoice.receipt_width" :label="__('Thermal receipt width')">
                        <flux:select.option value="80mm">80 mm</flux:select.option>
                        <flux:select.option value="58mm">58 mm</flux:select.option>
                    </flux:select>
                </div>
                <flux:switch wire:model="invoice.show_logo" :label="__('Show logo on A4 invoices')" />
                <flux:input wire:model="invoice.footer_note" :label="__('Footer note')" />
                <flux:textarea wire:model="invoice.terms" :label="__('Terms & conditions (A4 invoice)')" rows="3" />
            </div>
        </div>

        <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 self-start dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Tax') }}</flux:heading>
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.text>{{ __('Tax is off by default. Enable it only if the business is registered and you know the applicable rate. The rate is applied on the invoice subtotal after discount and recorded on each invoice.') }}</flux:callout.text>
            </flux:callout>
            <flux:switch wire:model.live="tax.enabled" :label="__('Charge tax on invoices')" />
            @if($tax['enabled'] ?? false)
                <flux:input wire:model="tax.label" :label="__('Tax label')" placeholder="GST" />
                <flux:input wire:model="tax.rate" :label="__('Tax rate (%)')" type="number" step="0.01" min="0" max="100" />
            @endif
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </form>
</div>
