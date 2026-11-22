@props([
    'sidebar' => false,
])

@php($name = setting('business.name', config('app.name')))

@if($sidebar)
    <a {{ $attributes->merge(['class' => 'flex items-center gap-2 px-1 py-1']) }} aria-label="{{ $name }}">
        <img src="{{ asset('images/brand/kdh-logo-600.png') }}" alt="{{ $name }}" class="h-11 w-auto object-contain" />
    </a>
@else
    <a {{ $attributes->merge(['class' => 'flex items-center gap-2']) }} aria-label="{{ $name }}">
        <img src="{{ asset('images/brand/kdh-logo-600.png') }}" alt="{{ $name }}" class="h-9 w-auto object-contain" />
    </a>
@endif
