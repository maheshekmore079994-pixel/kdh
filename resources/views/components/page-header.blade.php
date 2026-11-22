@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div>
        <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
        @if($subtitle)
            <flux:subheading>{{ $subtitle }}</flux:subheading>
        @endif
    </div>
    @if(trim($slot) !== '')
        <div class="flex flex-wrap items-center gap-2">
            {{ $slot }}
        </div>
    @endif
</div>
