<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        @php($user = auth()->user())
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-950">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                @can('billing')
                    <flux:sidebar.group :heading="__('Billing')" class="grid" expandable :expanded="request()->routeIs('pos', 'invoices.*', 'payments.*')">
                        <flux:sidebar.item icon="banknotes" :href="route('pos')" :current="request()->routeIs('pos')" wire:navigate>{{ __('New bill (POS)') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="document-text" :href="route('invoices.index')" :current="request()->routeIs('invoices.*')" wire:navigate>{{ __('Invoices') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="currency-rupee" :href="route('payments.index')" :current="request()->routeIs('payments.*')" wire:navigate>{{ __('Payments') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can('jobs')
                    <flux:sidebar.item icon="briefcase" :href="route('jobs.index')" :current="request()->routeIs('jobs.*')" wire:navigate>{{ __('Jobs') }}</flux:sidebar.item>
                @endcan

                @can('customers')
                    <flux:sidebar.item icon="users" :href="route('customers.index')" :current="request()->routeIs('customers.*')" wire:navigate>{{ __('Customers') }}</flux:sidebar.item>
                @endcan

                @can('expenses')
                    <flux:sidebar.item icon="receipt-percent" :href="route('expenses.index')" :current="request()->routeIs('expenses.*')" wire:navigate>{{ __('Expenses') }}</flux:sidebar.item>
                @endcan

                @if($user->can('inventory') || $user->can('purchases'))
                    <flux:sidebar.group :heading="__('Inventory')" class="grid" expandable :expanded="request()->routeIs('products.*', 'stock.*', 'purchases.*', 'suppliers.*')">
                        @can('inventory')
                            <flux:sidebar.item icon="cube" :href="route('products.index')" :current="request()->routeIs('products.*')" wire:navigate>{{ __('Products') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="archive-box" :href="route('stock.index')" :current="request()->routeIs('stock.*')" wire:navigate>{{ __('Stock movements') }}</flux:sidebar.item>
                        @endcan
                        @can('purchases')
                            <flux:sidebar.item icon="shopping-cart" :href="route('purchases.index')" :current="request()->routeIs('purchases.*')" wire:navigate>{{ __('Purchases') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="truck" :href="route('suppliers.index')" :current="request()->routeIs('suppliers.*')" wire:navigate>{{ __('Suppliers') }}</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif

                @can('reports')
                    <flux:sidebar.group :heading="__('Accounts')" class="grid" expandable :expanded="request()->routeIs('reports.*', 'tools.*')">
                        <flux:sidebar.item icon="chart-bar" :href="route('reports.index')" :current="request()->routeIs('reports.*')" wire:navigate>{{ __('Reports') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="calculator" :href="route('tools.calculator')" :current="request()->routeIs('tools.calculator')" wire:navigate>{{ __('Print cost calculator') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @can('website')
                    <flux:sidebar.group :heading="__('Website')" class="grid" expandable :expanded="request()->routeIs('enquiries.*', 'config.website')">
                        <flux:sidebar.item icon="inbox" :href="route('enquiries.index')" :current="request()->routeIs('enquiries.*')" wire:navigate>{{ __('Enquiries') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="globe-alt" :href="route('config.website')" :current="request()->routeIs('config.website')" wire:navigate>{{ __('Website content') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan

                @if($user->can('settings') || $user->can('users'))
                    <flux:sidebar.group :heading="__('Configuration')" class="grid" expandable :expanded="request()->routeIs('config.business', 'config.billing', 'config.catalogue', 'config.lists', 'config.documents', 'config.audit', 'users.*')">
                        @can('settings')
                            <flux:sidebar.item icon="building-storefront" :href="route('config.business')" :current="request()->routeIs('config.business')" wire:navigate>{{ __('Business details') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="document-text" :href="route('config.billing')" :current="request()->routeIs('config.billing')" wire:navigate>{{ __('Billing & tax') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-list" :href="route('config.catalogue')" :current="request()->routeIs('config.catalogue')" wire:navigate>{{ __('Services & prices') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="list-bullet" :href="route('config.lists')" :current="request()->routeIs('config.lists')" wire:navigate>{{ __('Lists & categories') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="folder" :href="route('config.documents')" :current="request()->routeIs('config.documents')" wire:navigate>{{ __('Documents & backups') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="shield-check" :href="route('config.audit')" :current="request()->routeIs('config.audit')" wire:navigate>{{ __('Audit log') }}</flux:sidebar.item>
                        @endcan
                        @can('users')
                            <flux:sidebar.item icon="user-group" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>{{ __('Users') }}</flux:sidebar.item>
                        @endcan
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" />
        </flux:sidebar>

        <!-- Mobile header -->
        <flux:header class="lg:hidden border-b border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-950">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile :initials="$user->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                        <flux:avatar :name="$user->name" :initials="$user->initials()" />
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <flux:heading class="truncate">{{ $user->name }}</flux:heading>
                            <flux:text class="truncate">{{ $user->email }}</flux:text>
                        </div>
                    </div>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('My account') }}</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast position="top right" />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
