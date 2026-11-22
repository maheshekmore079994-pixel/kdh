<div class="flex flex-col gap-6">
    <x-page-header :title="__('Dashboard')" :subtitle="$start->isSameDay($end) ? $start->format('l, d M Y') : $start->format('d M Y').' – '.$end->format('d M Y')">
        <div class="flex flex-wrap items-center gap-2">
            <flux:select wire:model.live="period" size="sm" class="min-w-40">
                <flux:select.option value="today">{{ __('Today') }}</flux:select.option>
                <flux:select.option value="yesterday">{{ __('Yesterday') }}</flux:select.option>
                <flux:select.option value="week">{{ __('This week') }}</flux:select.option>
                <flux:select.option value="month">{{ __('This month') }}</flux:select.option>
                <flux:select.option value="last_month">{{ __('Last month') }}</flux:select.option>
                <flux:select.option value="year">{{ __('This year') }}</flux:select.option>
                <flux:select.option value="custom">{{ __('Custom range') }}</flux:select.option>
            </flux:select>
            @if($period === 'custom')
                <flux:input type="date" wire:model.live="from" size="sm" />
                <flux:input type="date" wire:model.live="to" size="sm" />
            @endif
        </div>
    </x-page-header>

    {{-- Quick actions --}}
    <div class="flex flex-wrap gap-2">
        @can('billing')
            <flux:button :href="route('pos')" icon="banknotes" variant="primary" wire:navigate>{{ __('New bill') }}</flux:button>
        @endcan
        @can('jobs')
            <flux:button :href="route('jobs.create')" icon="briefcase" wire:navigate>{{ __('New job') }}</flux:button>
        @endcan
        @can('expenses')
            <flux:button :href="route('expenses.index', ['new' => 1])" icon="receipt-percent" wire:navigate>{{ __('Add expense') }}</flux:button>
        @endcan
        @can('customers')
            <flux:button :href="route('customers.index', ['new' => 1])" icon="user-plus" wire:navigate>{{ __('Add customer') }}</flux:button>
        @endcan
    </div>

    {{-- Money tiles --}}
    @if($sales)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card :label="__('Sales (invoiced)')" :value="money($sales['total'])" :hint="__(':n invoices', ['n' => $sales['invoices']])" icon="document-text" color="gold" :href="auth()->user()->can('billing') ? route('invoices.index') : null" />
            <x-stat-card :label="__('Receipts (cash in)')" :value="money($receipts['total'])" :hint="collect($receipts['methods'])->map(fn($m) => $m['name'].' '.money($m['amount']))->join(' · ') ?: __('No payments received')" icon="currency-rupee" color="green" :href="auth()->user()->can('billing') ? route('payments.index') : null" />
            @if($expenses !== null)
                <x-stat-card :label="__('Expenses')" :value="money($expenses)" icon="receipt-percent" color="red" :href="auth()->user()->can('expenses') ? route('expenses.index') : null" />
            @endif
            @if($profit)
                <x-stat-card :label="__('Profit estimate')" :value="money($profit['profit'])" :hint="__('Sales net of tax − product cost − expenses')" icon="chart-bar" :color="bccomp($profit['profit'], '0', 2) >= 0 ? 'green' : 'red'" :href="route('reports.show', 'profit-loss')" />
            @endif
        </div>
    @endif

    {{-- Jobs + dues + stock --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card :label="__('Jobs received')" :value="$jobs['received']" icon="briefcase" :href="auth()->user()->can('jobs') ? route('jobs.index') : null" />
        <x-stat-card :label="__('Jobs pending')" :value="$jobs['pending']" :hint="$jobs['overdue'] ? __(':n overdue', ['n' => $jobs['overdue']]) : null" icon="clock" :color="$jobs['overdue'] ? 'red' : 'amber'" :href="auth()->user()->can('jobs') ? route('jobs.index', ['status' => 'open']) : null" />
        <x-stat-card :label="__('Ready / delivered')" :value="$jobs['completed'].' / '.$jobs['delivered']" :hint="__('Completed awaiting pickup / delivered in period')" icon="check-circle" color="green" />
        @if($dues !== null)
            <x-stat-card :label="__('Customer dues')" :value="money($dues)" :hint="__('Outstanding on all invoices')" icon="exclamation-triangle" :color="bccomp($dues, '0', 2) > 0 ? 'amber' : 'zinc'" :href="auth()->user()->can('reports') ? route('reports.show', 'customer-dues') : null" />
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Recent activity --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-4 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-3">{{ __('Recent transactions') }}</flux:heading>
            @if($activity->isEmpty())
                <x-empty-state icon="document-text" :title="__('No transactions yet')" :description="__('Bills, payments and expenses will appear here as they are recorded.')" />
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                    @foreach($activity as $row)
                        <a href="{{ $row['url'] ?? '#' }}" class="flex items-center gap-3 py-2.5 text-sm" @if($row['url']) wire:navigate @endif>
                            <flux:icon :name="match($row['type']) { 'invoice' => 'document-text', 'payment' => 'currency-rupee', default => 'receipt-percent' }" class="size-5 shrink-0 {{ match($row['type']) { 'invoice' => 'text-zinc-500', 'payment' => 'text-emerald-600', default => 'text-red-500' } }}" />
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium text-zinc-900 dark:text-white">{{ $row['title'] }}</div>
                                <div class="truncate text-xs text-zinc-500">{{ $row['subtitle'] }} · {{ $row['at']->format('d M, h:i A') }}</div>
                            </div>
                            @if($row['status'])
                                <flux:badge size="sm" :color="$row['status']->color()">{{ $row['status']->label() }}</flux:badge>
                            @endif
                            <div class="tabular-nums font-medium {{ $row['type'] === 'expense' ? 'text-red-600' : 'text-zinc-900 dark:text-white' }}">{{ $row['type'] === 'expense' ? '−' : '' }}{{ money($row['amount']) }}</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Low stock --}}
        @can('inventory')
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Low stock') }}</flux:heading>
                    @if($lowStockCount > 0)
                        <flux:badge color="red" size="sm">{{ $lowStockCount }}</flux:badge>
                    @endif
                </div>
                @if($lowStock->isEmpty())
                    <flux:text>{{ __('All products are above their reorder level.') }}</flux:text>
                @else
                    <ul class="divide-y divide-zinc-100 text-sm dark:divide-zinc-700">
                        @foreach($lowStock as $product)
                            <li class="flex items-center justify-between py-2">
                                <a href="{{ route('products.show', $product) }}" class="truncate font-medium hover:underline" wire:navigate>{{ $product->name }}</a>
                                <span class="tabular-nums text-red-600">{{ qty($product->current_stock) }} {{ $product->unit }} <span class="text-zinc-400">/ {{ qty($product->reorder_level) }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                    @if($lowStockCount > $lowStock->count())
                        <flux:link :href="route('products.index', ['filter' => 'low'])" class="mt-2 block text-sm" wire:navigate>{{ __('View all :n low-stock products', ['n' => $lowStockCount]) }}</flux:link>
                    @endif
                @endif
            </div>
        @endcan
    </div>
</div>
