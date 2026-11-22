<x-layouts::auth :title="__('First-time setup')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Welcome — first-time setup')" :description="__('Create the administrator account. This page appears only once, while no users exist.')" />

        <form method="POST" action="{{ route('setup.store') }}" class="flex flex-col gap-5">
            @csrf
            <flux:input name="name" :label="__('Your name')" :value="old('name')" required autofocus />
            <flux:input name="email" type="email" :label="__('Email (used to log in)')" :value="old('email')" required />
            <flux:input name="password" type="password" :label="__('Password (at least 8 characters)')" required viewable autocomplete="new-password" />
            <flux:input name="password_confirmation" type="password" :label="__('Confirm password')" required viewable autocomplete="new-password" />
            <flux:button type="submit" variant="primary" class="w-full">{{ __('Create administrator') }}</flux:button>
        </form>

        <flux:text class="text-center text-xs">{{ __('Service catalogue names, payment methods and settings keys are set up automatically. No prices or transactions are created.') }}</flux:text>
    </div>
</x-layouts::auth>
