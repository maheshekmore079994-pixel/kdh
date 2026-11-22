@props(['label', 'value', 'hint' => null, 'icon' => null, 'color' => 'zinc', 'href' => null])

@php
    $tag = $href ? 'a' : 'div';
    $colors = [
        'zinc' => 'text-zinc-500',
        'green' => 'text-emerald-600',
        'red' => 'text-red-600',
        'amber' => 'text-amber-600',
        'blue' => 'text-blue-600',
        'gold' => 'text-[#B9821F]',
    ];
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->merge(['class' => 'flex flex-col justify-between rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-700 dark:bg-zinc-800 '.($href ? 'transition hover:border-zinc-300 hover:shadow-sm' : '')]) }}>
    <div class="flex items-center justify-between gap-2">
        <span class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
        @if($icon)
            <flux:icon :name="$icon" class="size-4 {{ $colors[$color] ?? $colors['zinc'] }}" />
        @endif
    </div>
    <div class="mt-2 text-2xl font-semibold tabular-nums text-zinc-900 dark:text-white">{{ $value }}</div>
    @if($hint)
        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</div>
    @endif
</{{ $tag }}>
