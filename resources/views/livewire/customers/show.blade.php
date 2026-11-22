<div>
    <x-page-header :title="$customer->name" :subtitle="$customer->mobile.($customer->email ? ' · '.$customer->email : '')">
        @can('billing')
            <flux:button :href="route('pos', ['customer' => $customer->id])" icon="banknotes" variant="primary" size="sm" wire:navigate>{{ __('New bill') }}</flux:button>
        @endcan
        @can('jobs')
            <flux:button :href="route('jobs.create', ['customer' => $customer->id])" icon="briefcase" size="sm" wire:navigate>{{ __('New job') }}</flux:button>
        @endcan
        <flux:button :href="route('customers.index')" size="sm" variant="ghost" wire:navigate>{{ __('All customers') }}</flux:button>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        <x-stat-card :label="__('Outstanding due')" :value="money($due)" :color="bccomp($due, '0', 2) > 0 ? 'amber' : 'green'" icon="exclamation-triangle" />
        <x-stat-card :label="__('Total billed')" :value="money($stats['billed'])" icon="document-text" />
        <x-stat-card :label="__('Bills')" :value="$stats['invoices']" icon="document-text" />
        <x-stat-card :label="__('Jobs')" :value="$stats['jobs']" icon="briefcase" />
    </div>

    @if($customer->address || $customer->notes)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800">
            @if($customer->address)<div><span class="text-zinc-500">{{ __('Address:') }}</span> {{ $customer->address }}</div>@endif
            @if($customer->notes)<div class="mt-1"><span class="text-zinc-500">{{ __('Notes:') }}</span> {{ $customer->notes }}</div>@endif
        </div>
    @endif

    <flux:navbar class="mb-4 border-b border-zinc-200 dark:border-zinc-700">
        <flux:navbar.item wire:click="$set('tab', 'invoices')" :current="$tab === 'invoices'" class="cursor-pointer">{{ __('Bills') }}</flux:navbar.item>
        <flux:navbar.item wire:click="$set('tab', 'jobs')" :current="$tab === 'jobs'" class="cursor-pointer">{{ __('Jobs') }}</flux:navbar.item>
        <flux:navbar.item wire:click="$set('tab', 'payments')" :current="$tab === 'payments'" class="cursor-pointer">{{ __('Payments') }}</flux:navbar.item>
        <flux:navbar.item wire:click="$set('tab', 'documents')" :current="$tab === 'documents'" class="cursor-pointer">{{ __('Documents') }}</flux:navbar.item>
    </flux:navbar>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        @if($tab === 'invoices')
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Invoice') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column class="text-end">{{ __('Total') }}</flux:table.column>
                    <flux:table.column class="text-end">{{ __('Paid') }}</flux:table.column>
                    <flux:table.column class="text-end">{{ __('Due') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($invoices as $inv)
                        <flux:table.row :key="$inv->id">
                            <flux:table.cell><a href="{{ route('invoices.show', $inv) }}" class="font-medium hover:underline" wire:navigate>{{ $inv->invoice_number }}</a></flux:table.cell>
                            <flux:table.cell>{{ $inv->invoice_date->format('d M Y') }}</flux:table.cell>
                            <flux:table.cell class="text-end tabular-nums">{{ money($inv->total) }}</flux:table.cell>
                            <flux:table.cell class="text-end tabular-nums">{{ money($inv->paid_amount) }}</flux:table.cell>
                            <flux:table.cell class="text-end tabular-nums">{{ money($inv->balance_due) }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$inv->status->color()">{{ $inv->status->label() }}</flux:badge></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="6"><x-empty-state icon="document-text" :title="__('No bills yet')" class="border-0" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @elseif($tab === 'jobs')
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Job') }}</flux:table.column>
                    <flux:table.column>{{ __('Work') }}</flux:table.column>
                    <flux:table.column>{{ __('Received') }}</flux:table.column>
                    <flux:table.column class="text-end">{{ __('Amount') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($jobs as $job)
                        <flux:table.row :key="$job->id">
                            <flux:table.cell><a href="{{ route('jobs.show', $job) }}" class="font-medium hover:underline" wire:navigate>{{ $job->job_number }}</a></flux:table.cell>
                            <flux:table.cell>{{ $job->title }}</flux:table.cell>
                            <flux:table.cell>{{ $job->received_at->format('d M Y') }}</flux:table.cell>
                            <flux:table.cell class="text-end tabular-nums">{{ money($job->total_amount) }}</flux:table.cell>
                            <flux:table.cell><flux:badge size="sm" :color="$job->status->color()">{{ $job->status->label() }}</flux:badge></flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="5"><x-empty-state icon="briefcase" :title="__('No jobs yet')" class="border-0" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @elseif($tab === 'payments')
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Receipt') }}</flux:table.column>
                    <flux:table.column>{{ __('Invoice') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Method') }}</flux:table.column>
                    <flux:table.column class="text-end">{{ __('Amount') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse($payments as $p)
                        <flux:table.row :key="$p->id" class="{{ $p->isReversed() ? 'opacity-50' : '' }}">
                            <flux:table.cell class="font-medium">{{ $p->payment_number }} @if($p->isReversed())<flux:badge size="sm" color="zinc">{{ __('Reversed') }}</flux:badge>@endif</flux:table.cell>
                            <flux:table.cell><a href="{{ route('invoices.show', $p->invoice) }}" class="hover:underline" wire:navigate>{{ $p->invoice?->invoice_number }}</a></flux:table.cell>
                            <flux:table.cell>{{ $p->paid_at->format('d M Y, h:i A') }}</flux:table.cell>
                            <flux:table.cell>{{ $p->method?->name }}</flux:table.cell>
                            <flux:table.cell class="text-end tabular-nums">{{ money($p->amount) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row><flux:table.cell colspan="5"><x-empty-state icon="currency-rupee" :title="__('No payments yet')" class="border-0" /></flux:table.cell></flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @else
            <div class="p-4">
                <livewire:documents.manager :model="$customer" :key="'docs-customer-'.$customer->id" />
            </div>
        @endif
    </div>
</div>
