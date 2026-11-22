<div>
    <x-page-header :title="__('Documents & backups')" :subtitle="__('Attachment limits, retention policy and database backups.')" />

    <div class="grid gap-6 lg:grid-cols-2">
        <form wire:submit="save" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 self-start dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Customer documents & attachments') }}</flux:heading>
            <flux:text class="text-sm">{{ __('Allowed types: PDF, JPG, PNG, WEBP, DOCX, XLSX. Files are stored privately and can only be downloaded by logged-in staff with access to the related record.') }}</flux:text>
            <flux:input wire:model="form.max_size_mb" type="number" min="1" max="50" :label="__('Maximum file size (MB)')" />
            <flux:input wire:model="form.retention_days" type="number" min="0" max="3650" :label="__('Delete uploaded files after (days)')" :description="__('0 = keep until deleted manually. The scheduled command documents:prune removes expired files.')" />
            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>

        <div class="space-y-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Database backups') }}</flux:heading>
                <flux:button wire:click="runBackup" wire:loading.attr="disabled" icon="arrow-down-tray" size="sm" variant="primary">{{ __('Back up now') }}</flux:button>
            </div>
            <flux:text class="text-sm">{{ __('Backups are stored on disk “:disk” at :path. A daily backup runs automatically when the scheduler is active. Copy backups off the server regularly.', ['disk' => $backupDisk, 'path' => $backupPath]) }}</flux:text>
            @if($backupOutput)
                <pre class="max-h-48 overflow-auto rounded-lg bg-zinc-900 p-3 text-xs text-zinc-100">{{ $backupOutput }}</pre>
            @endif
            @if(empty($backups))
                <flux:text class="text-sm text-zinc-500">{{ __('No backups found yet.') }}</flux:text>
            @else
                <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-700">
                    @foreach($backups as $b)
                        <li class="flex items-center justify-between py-2">
                            <span class="truncate font-mono text-xs">{{ $b['name'] }}</span>
                            <span class="text-xs text-zinc-500">{{ number_format($b['size'] / 1024, 0) }} KB · {{ $b['at']->timezone(config('app.timezone'))->format('d M Y, h:i A') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
