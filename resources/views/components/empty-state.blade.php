@props(['icon' => 'inbox', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700']) }}>
    <flux:icon :name="$icon" class="mb-3 size-10 text-zinc-400" />
    <flux:heading size="lg">{{ $title }}</flux:heading>
    @if($description)
        <flux:text class="mt-1 max-w-md">{{ $description }}</flux:text>
    @endif
    @if(trim($slot) !== '')
        <div class="mt-4 flex gap-2">{{ $slot }}</div>
    @endif
</div>
