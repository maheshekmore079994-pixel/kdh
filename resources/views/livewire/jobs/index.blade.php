<div>
    <x-page-header :title="__('Jobs')" :subtitle="__('Work received for later delivery. Payment status comes from the linked bill.')">
        <flux:button :href="route('jobs.create')" icon="plus" variant="primary" size="sm" wire:navigate>{{ __('New job') }}</flux:button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Job no., customer, mobile, title')" size="sm" />
        <flux:select wire:model.live="status" size="sm">
            <flux:select.option value="open">{{ __('Open (pending + processing)') }}</flux:select.option>
            <flux:select.option value="overdue">{{ __('Overdue') }}</flux:select.option>
            @foreach($statuses as $s)<flux:select.option :value="$s->value">{{ $s->label() }}</flux:select.option>@endforeach
            <flux:select.option value="all">{{ __('All') }}</flux:select.option>
        </flux:select>
        <flux:input type="date" wire:model.live="from" size="sm" />
        <flux:input type="date" wire:model.live="to" size="sm" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$jobs">
            <flux:table.columns>
                <flux:table.column>{{ __('Job') }}</flux:table.column>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Work') }}</flux:table.column>
                <flux:table.column>{{ __('Due') }}</flux:table.column>
                <flux:table.column class="text-end">{{ __('Amount') }}</flux:table.column>
                <flux:table.column>{{ __('Payment') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($jobs as $job)
                    @php($overdue = $job->status->isOpen() && $job->expected_at && $job->expected_at->isPast())
                    <flux:table.row :key="$job->id">
                        <flux:table.cell>
                            <a href="{{ route('jobs.show', $job) }}" class="font-medium text-zinc-900 hover:underline dark:text-white" wire:navigate>{{ $job->job_number }}</a>
                            <div class="text-xs text-zinc-500">{{ $job->received_at->format('d M, h:i A') }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $job->customer?->name }}<div class="text-xs text-zinc-500">{{ $job->customer?->mobile }}</div></flux:table.cell>
                        <flux:table.cell class="max-w-xs"><div class="truncate">{{ $job->title }}</div>@if($job->assignee)<div class="text-xs text-zinc-500">{{ $job->assignee->name }}</div>@endif</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap {{ $overdue ? 'text-red-600 font-medium' : '' }}">{{ $job->expected_at?->format('d M, h:i A') ?? '—' }}</flux:table.cell>
                        <flux:table.cell class="text-end tabular-nums">{{ money($job->total_amount) }}</flux:table.cell>
                        <flux:table.cell>
                            @if($job->invoice)
                                <flux:badge size="sm" :color="$job->invoice->status->color()">{{ $job->invoice->status->label() }}</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">{{ __('Not billed') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell><flux:badge size="sm" :color="$job->status->color()">{{ $job->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-end whitespace-nowrap">
                            @foreach($job->status->allowedTransitions() as $next)
                                @if(in_array($next, [\App\Enums\JobStatus::Processing, \App\Enums\JobStatus::Completed, \App\Enums\JobStatus::Delivered], true))
                                    <flux:button wire:click="setStatus({{ $job->id }}, '{{ $next->value }}')" size="xs" variant="ghost">{{ $next->label() }}</flux:button>
                                @endif
                            @endforeach
                            <flux:button :href="route('jobs.show', $job)" size="xs" variant="ghost" icon="eye" wire:navigate />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="8"><x-empty-state icon="briefcase" :title="__('No jobs found')" class="border-0"><flux:button :href="route('jobs.create')" variant="primary" size="sm" wire:navigate>{{ __('Create a job') }}</flux:button></x-empty-state></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
