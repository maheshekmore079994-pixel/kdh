<div>
    <x-page-header :title="__('Website content')" :subtitle="__('Text shown on the public website. Contact details, hours and location come from Business details.')">
        <flux:button :href="route('home')" target="_blank" icon="arrow-top-right-on-square" size="sm">{{ __('View website') }}</flux:button>
    </x-page-header>

    <form wire:submit="save" class="space-y-6">
        <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:switch wire:model="form.enabled" :label="__('Website enabled')" :description="__('When off, public pages show a short “coming soon” notice with contact details.')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.hero_title" :label="__('Home page headline')" :placeholder="__('Leave blank to use the business name')" />
                <flux:input wire:model="form.meta_description" :label="__('Search engine description (max 160 characters)')" />
            </div>
            <flux:textarea wire:model="form.hero_subtitle" :label="__('Home page intro line')" rows="2" />
            <flux:textarea wire:model="form.about" :label="__('About us')" rows="6" :description="__('Plain text. Blank lines start new paragraphs. Do not add claims, awards or reviews that are not true.')" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:switch wire:model="form.show_opening_hours" :label="__('Show opening hours (if set)')" />
                <flux:switch wire:model="form.show_map" :label="__('Show map link (if set)')" />
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:textarea wire:model="form.privacy_policy" :label="__('Privacy policy')" rows="10" :description="__('Blank = a generic default notice is shown.')" />
            </div>
            <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:textarea wire:model="form.terms" :label="__('Terms of service')" rows="10" :description="__('Blank = a generic default notice is shown.')" />
            </div>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</div>
