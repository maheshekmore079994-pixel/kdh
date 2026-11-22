<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Access denied') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50 flex items-center justify-center p-6">
    <div class="max-w-md text-center">
        <img src="{{ asset('images/brand/kdh-mark.png') }}" alt="" class="mx-auto mb-4 h-16 w-auto">
        <h1 class="text-2xl font-semibold text-zinc-900">{{ __('You do not have access to this page') }}</h1>
        <p class="mt-2 text-zinc-600">{{ $exception->getMessage() ?: __('Ask an administrator to grant you this permission.') }}</p>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="mt-6 inline-block rounded-lg bg-zinc-900 px-4 py-2 text-white">{{ __('Go back') }}</a>
    </div>
</body>
</html>
