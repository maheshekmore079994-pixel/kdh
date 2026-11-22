<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-zinc-50 p-6 md:p-10 dark:bg-transparent">
            <div class="flex w-full max-w-sm flex-col gap-6">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <img src="{{ asset('images/brand/kdh-logo-600.png') }}" alt="{{ setting('business.name', config('app.name')) }}" class="h-24 w-auto object-contain" />
                    <span class="sr-only">{{ setting('business.name', config('app.name')) }}</span>
                </a>
                <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-6">
                        {{ $slot }}
                    </div>
                </div>
                <p class="text-center text-xs text-zinc-500">{{ __('Staff access only. Internet connection required.') }}</p>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
