<div>
    <x-page-header :title="__('Audit log')" :subtitle="__('Who changed what and when. Financial edits, cancellations, reversals, settings and user changes are recorded here.')" />

    <div class="mb-4 grid gap-3 sm:grid-cols-4">
        <flux:select wire:model.live="action" size="sm">
            <flux:select.option value="">{{ __('All actions') }}</flux:select.option>
            @foreach($actions as $a)
                <flux:select.option :value="$a">{{ $a }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="user" size="sm">
            <flux:select.option value="">{{ __('All users') }}</flux:select.option>
            @foreach($users as $u)
                <flux:select.option :value="$u->id">{{ $u->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:input type="date" wire:model.live="from" size="sm" />
        <flux:input type="date" wire:model.live="to" size="sm" />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <flux:table :paginate="$logs">
            <flux:table.columns>
                <flux:table.column>{{ __('When') }}</flux:table.column>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Action') }}</flux:table.column>
                <flux:table.column>{{ __('Record') }}</flux:table.column>
                <flux:table.column>{{ __('Changes') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($logs as $log)
                    <flux:table.row :key="$log->id">
                        <flux:table.cell class="whitespace-nowrap text-xs">{{ $log->created_at->format('d M Y, h:i:s A') }}</flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $log->user?->name ?? __('System') }}<div class="text-zinc-400">{{ $log->ip }}</div></flux:table.cell>
                        <flux:table.cell><flux:badge size="sm">{{ $log->action }}</flux:badge></flux:table.cell>
                        <flux:table.cell class="text-xs">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}@if($log->reason)<div class="text-zinc-500">{{ __('Reason:') }} {{ $log->reason }}</div>@endif</flux:table.cell>
                        <flux:table.cell class="max-w-md text-xs">
                            @if($log->new_values)
                                <details>
                                    <summary class="cursor-pointer text-zinc-600 dark:text-zinc-300">{{ count($log->new_values) }} {{ __('field(s)') }}</summary>
                                    <dl class="mt-1 space-y-0.5">
                                        @foreach($log->new_values as $field => $value)
                                            <div class="flex gap-2"><dt class="w-32 shrink-0 text-zinc-500">{{ $field }}</dt><dd class="break-all">@if(isset($log->old_values[$field]))<span class="text-red-500 line-through">{{ is_array($log->old_values[$field]) ? json_encode($log->old_values[$field]) : $log->old_values[$field] }}</span> → @endif<span class="text-emerald-600">{{ is_array($value) ? json_encode($value) : $value }}</span></dd></div>
                                        @endforeach
                                    </dl>
                                </details>
                            @else
                                —
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="5"><x-empty-state icon="shield-check" :title="__('No audit entries match')" class="border-0" /></flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>
