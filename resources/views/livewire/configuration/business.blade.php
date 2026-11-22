<div>
    <x-page-header :title="__('Business details')" :subtitle="__('Shown on invoices, receipts and the public website. Leave unknown fields blank; they are hidden automatically.')" />

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:input wire:model="form.name" :label="__('Business name')" required />
            <flux:input wire:model="form.tagline" :label="__('Tagline')" />
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.email" :label="__('Email')" type="email" />
                <flux:input wire:model="form.phone" :label="__('Phone')" />
                <flux:input wire:model="form.whatsapp" :label="__('WhatsApp number')" :description="__('Used for the website WhatsApp button')" />
            </div>
            <flux:textarea wire:model="form.address" :label="__('Address')" rows="2" />
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="form.city" :label="__('City / village')" />
                <flux:input wire:model="form.state" :label="__('State')" />
                <flux:input wire:model="form.pincode" :label="__('PIN code')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.gstin" :label="__('GSTIN (optional)')" :description="__('Only if the business is GST registered')" />
                <flux:input wire:model="form.map_url" :label="__('Google Maps link (optional)')" type="url" />
            </div>
            <flux:textarea wire:model="form.opening_hours" :label="__('Opening hours')" rows="3" :description="__('One line per day, e.g. Mon–Sat: 9:00 AM – 8:00 PM. Leave blank to hide on the website.')" />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </div>

        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Logo') }}</flux:heading>
            <div class="flex items-center justify-center rounded-lg bg-zinc-50 p-4 dark:bg-zinc-900">
                <img src="{{ $logoUrl }}" alt="{{ __('Current logo') }}" class="max-h-32 w-auto object-contain" />
            </div>
            <flux:input type="file" wire:model="logo" :label="__('Replace logo (PNG/JPG, max 2 MB)')" accept="image/png,image/jpeg,image/webp" />
            @if($logo)
                <flux:text class="text-xs">{{ __('New logo selected. Click Save to apply.') }}</flux:text>
            @endif
            <flux:text class="text-xs">{{ __('The supplied brand logo is used by default.') }}</flux:text>
        </div>
    </form>
</div>
