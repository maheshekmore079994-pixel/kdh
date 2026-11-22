<div>
    <x-page-header :title="$job ? __('Edit job :n', ['n' => $job->job_number]) : __('New job')" :subtitle="__('Record the work received. You can bill it now or later from the job page.')">
        <flux:button :href="$job ? route('jobs.show', $job) : route('jobs.index')" variant="ghost" size="sm" wire:navigate>{{ __('Cancel') }}</flux:button>
    </x-page-header>

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-800">
            <div>
                <flux:label>{{ __('Customer') }} <span class="text-red-500">*</span></flux:label>
                <livewire:customers.picker wire:model="customerId" :required="true" />
                <flux:error name="customerId" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="serviceId" :label="__('Service')" :placeholder="__('Choose a service (optional)')">
                    <flux:select.option value="">{{ __('— Custom work —') }}</flux:select.option>
                    @foreach($services as $s)
                        <flux:select.option :value="$s->id">{{ $s->category?->name }} › {{ $s->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                @if($variants->isNotEmpty())
                    <flux:select wire:model.live="variantId" :label="__('Variant / rate')">
                        <flux:select.option value="">{{ __('Select') }}</flux:select.option>
                        @foreach($variants as $vr)
                            <flux:select.option :value="$vr->id">{{ $vr->name }} — {{ money($vr->unit_price) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif
            </div>

            <flux:input wire:model="title" :label="__('Job title')" required :placeholder="__('e.g. PAN card application, 50 colour prints')" />
            <flux:textarea wire:model="description" :label="__('Details')" rows="3" :placeholder="__('Documents received, specifications, pages, sizes…')" />

            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model.live.debounce.300ms="quantity" type="number" step="any" min="0" :label="__('Quantity')" required />
                <flux:input wire:model.live.debounce.300ms="unitPrice" type="number" step="0.01" min="0" :label="__('Price per unit (₹)')" required />
                <div>
                    <flux:label>{{ __('Total') }}</flux:label>
                    <div class="mt-2 text-xl font-semibold tabular-nums">{{ money($total) }}</div>
                </div>
            </div>

            <flux:textarea wire:model="notes" :label="__('Internal notes')" rows="2" :description="__('Not printed. Never store passwords, OTPs or PINs.')" />
        </div>

        <div class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 self-start dark:border-zinc-700 dark:bg-zinc-800">
            <flux:input wire:model="receivedAt" type="datetime-local" :label="__('Received on')" required />
            <flux:input wire:model="expectedAt" type="datetime-local" :label="__('Expected delivery')" />
            <flux:select wire:model="assignedTo" :label="__('Assigned to')">
                <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                @foreach($operators as $op)<flux:select.option :value="$op->id">{{ $op->name }}</flux:select.option>@endforeach
            </flux:select>
            <flux:button type="submit" variant="primary" class="w-full" icon="check">{{ $job ? __('Save changes') : __('Create job') }}</flux:button>
        </div>
    </form>
</div>
