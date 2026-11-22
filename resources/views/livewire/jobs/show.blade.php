<div>
    @php($overdue = $job->status->isOpen() && $job->expected_at && $job->expected_at->isPast())
    <x-page-header :title="$job->job_number.' · '.$job->title" :subtitle="$job->customer->name.' · '.$job->customer->mobile">
        <flux:badge :color="$job->status->color()">{{ $job->status->label() }}</flux:badge>
        @foreach($transitions as $next)
            @if($next === \App\Enums\JobStatus::Cancelled)
                <flux:modal.trigger name="cancel-job"><flux:button size="sm" variant="danger" icon="x-circle">{{ __('Cancel job') }}</flux:button></flux:modal.trigger>
            @else
                <flux:button wire:click="setStatus('{{ $next->value }}')" size="sm" :variant="$next === \App\Enums\JobStatus::Delivered || $next === \App\Enums\JobStatus::Completed ? 'primary' : 'filled'">{{ __('Mark :s', ['s' => $next->label()]) }}</flux:button>
            @endif
        @endforeach
        <flux:button :href="route('print.job', $job)" target="_blank" size="sm" icon="printer">{{ __('Job receipt') }}</flux:button>
        @if($job->status->isOpen())<flux:button :href="route('jobs.edit', $job)" size="sm" variant="ghost" icon="pencil-square" wire:navigate>{{ __('Edit') }}</flux:button>@endif
        <flux:button :href="route('jobs.index')" size="sm" variant="ghost" wire:navigate>{{ __('All jobs') }}</flux:button>
    </x-page-header>

    @if($overdue)
        <flux:callout variant="warning" icon="clock" class="mb-4"><flux:callout.text>{{ __('Expected delivery was :d.', ['d' => $job->expected_at->format('d M Y, h:i A')]) }}</flux:callout.text></flux:callout>
    @endif
    @if($job->status === \App\Enums\JobStatus::Delivered && $job->invoice && bccomp($job->invoice->balance_due, '0', 2) > 0)
        <flux:callout variant="danger" icon="exclamation-triangle" class="mb-4"><flux:callout.text>{{ __('Delivered with :due still due.', ['due' => money($job->invoice->balance_due)]) }}</flux:callout.text></flux:callout>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <dl class="grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div><dt class="text-zinc-500">{{ __('Service') }}</dt><dd>{{ $job->service?->name ?? __('Custom') }}@if($job->variant) – {{ $job->variant->name }}@endif</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Quantity × price') }}</dt><dd>{{ qty($job->quantity) }} × {{ money($job->unit_price) }} = <b>{{ money($job->total_amount) }}</b></dd></div>
                    <div><dt class="text-zinc-500">{{ __('Received') }}</dt><dd>{{ $job->received_at->format('d M Y, h:i A') }} · {{ $job->creator?->name }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Expected delivery') }}</dt><dd>{{ $job->expected_at?->format('d M Y, h:i A') ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Assigned to') }}</dt><dd>{{ $job->assignee?->name ?? __('Unassigned') }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Delivered') }}</dt><dd>{{ $job->delivered_at?->format('d M Y, h:i A') ?? '—' }}</dd></div>
                </dl>
                @if($job->description)<div class="mt-4 border-t border-zinc-100 pt-3 text-sm whitespace-pre-line dark:border-zinc-700">{{ $job->description }}</div>@endif
                @if($job->notes)<div class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-200"><b>{{ __('Internal notes:') }}</b> {{ $job->notes }}</div>@endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">{{ __('Status history') }}</flux:heading>
                <ol class="space-y-2 text-sm">
                    @foreach($job->histories->reverse() as $h)
                        <li class="flex items-start gap-3">
                            <flux:badge size="sm" :color="\App\Enums\JobStatus::from($h->to_status)->color()">{{ \App\Enums\JobStatus::from($h->to_status)->label() }}</flux:badge>
                            <div><div>{{ $h->created_at->format('d M Y, h:i A') }} · {{ $h->user?->name }}</div>@if($h->note)<div class="text-zinc-500">{{ $h->note }}</div>@endif</div>
                        </li>
                    @endforeach
                </ol>
                @if($job->status->isOpen())
                    <flux:input wire:model="statusNote" :placeholder="__('Optional note for the next status change')" size="sm" class="mt-3" />
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">{{ __('Documents') }}</flux:heading>
                <livewire:documents.manager :model="$job" :key="'docs-job-'.$job->id" />
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <flux:heading size="lg" class="mb-3">{{ __('Billing') }}</flux:heading>
                @if($job->invoice)
                    <dl class="space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Invoice') }}</dt><dd><a href="{{ route('invoices.show', $job->invoice) }}" class="font-medium hover:underline" wire:navigate>{{ $job->invoice->invoice_number }}</a></dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Total') }}</dt><dd class="tabular-nums">{{ money($job->invoice->total) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-zinc-500">{{ __('Paid (advance)') }}</dt><dd class="tabular-nums text-emerald-600">{{ money($job->invoice->paid_amount) }}</dd></div>
                        <div class="flex justify-between font-semibold"><dt>{{ __('Balance') }}</dt><dd class="tabular-nums {{ bccomp($job->invoice->balance_due, '0', 2) > 0 ? 'text-amber-600' : '' }}">{{ money($job->invoice->balance_due) }}</dd></div>
                    </dl>
                    <div class="mt-3"><flux:badge :color="$job->invoice->status->color()">{{ $job->invoice->status->label() }}</flux:badge></div>
                    @can('billing')
                        @if(bccomp($job->invoice->balance_due, '0', 2) > 0 && !$job->invoice->isCancelled())
                            <flux:modal.trigger name="job-payment"><flux:button class="mt-3 w-full" variant="primary" icon="currency-rupee" size="sm">{{ __('Record payment') }}</flux:button></flux:modal.trigger>
                        @endif
                    @endcan
                @else
                    <flux:text class="text-sm">{{ __('Not billed yet. Billing creates an invoice for :amt so advances and the balance can be tracked.', ['amt' => money($job->total_amount)]) }}</flux:text>
                    @can('billing')
                        @if($job->status !== \App\Enums\JobStatus::Cancelled)
                            <flux:button wire:click="bill" class="mt-3 w-full" variant="primary" icon="document-text" size="sm" wire:confirm="{{ __('Create invoice for this job?') }}">{{ __('Bill this job') }}</flux:button>
                        @endif
                    @endcan
                @endif
            </div>
        </div>
    </div>

    <flux:modal name="job-payment" class="w-full max-w-md">
        <form wire:submit="recordPayment" class="space-y-4">
            <flux:heading size="lg">{{ __('Record payment / advance') }}</flux:heading>
            <flux:input wire:model="payAmount" type="number" step="0.01" min="0.01" :label="__('Amount (₹)')" required autofocus />
            <flux:select wire:model="payMethodId" :label="__('Method')">@foreach($methods as $m)<flux:select.option :value="$m->id">{{ $m->name }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="payReference" :label="__('Reference (optional)')" />
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close><flux:button type="submit" variant="primary">{{ __('Confirm') }}</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="cancel-job" class="w-full max-w-md">
        <form wire:submit="setStatus('cancelled')" class="space-y-4">
            <flux:heading size="lg">{{ __('Cancel job :n', ['n' => $job->job_number]) }}</flux:heading>
            <flux:textarea wire:model="cancelNote" :label="__('Reason')" rows="2" required autofocus />
            @if($job->invoice && !$job->invoice->isCancelled())<flux:text class="text-sm text-amber-700">{{ __('The linked invoice :n stays as is. Cancel or settle it separately.', ['n' => $job->invoice->invoice_number]) }}</flux:text>@endif
            <div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">{{ __('Keep job') }}</flux:button></flux:modal.close><flux:button type="submit" variant="danger">{{ __('Cancel job') }}</flux:button></div>
        </form>
    </flux:modal>
</div>
