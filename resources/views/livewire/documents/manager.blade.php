<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <flux:text class="text-sm">{{ __('Private attachments. PDF, JPG, PNG, WEBP, DOCX, XLSX up to :mb MB. Only staff can download.', ['mb' => $maxMb]) }}</flux:text>
        <flux:input type="file" wire:model="upload" size="sm" accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx" class="max-w-xs" />
    </div>
    <div wire:loading wire:target="upload" class="text-xs text-zinc-500">{{ __('Uploading…') }}</div>
    <flux:error name="upload" />

    @if($documents->isEmpty())
        <flux:text class="text-sm text-zinc-500">{{ __('No files attached.') }}</flux:text>
    @else
        <ul class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 text-sm dark:divide-zinc-700 dark:border-zinc-700">
            @foreach($documents as $doc)
                <li class="flex items-center justify-between gap-3 px-3 py-2">
                    <div class="min-w-0">
                        <a href="{{ route('documents.download', $doc) }}" class="truncate font-medium hover:underline">{{ $doc->original_name }}</a>
                        <div class="text-xs text-zinc-500">{{ $doc->humanSize() }} · {{ $doc->created_at->format('d M Y, h:i A') }} · {{ $doc->uploader?->name }}@if($doc->expires_at) · {{ __('expires') }} {{ $doc->expires_at->format('d M Y') }}@endif</div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        <flux:button :href="route('documents.download', $doc)" size="xs" variant="ghost" icon="arrow-down-tray" />
                        <flux:button wire:click="delete({{ $doc->id }})" wire:confirm="{{ __('Delete this file?') }}" size="xs" variant="ghost" icon="trash" />
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
