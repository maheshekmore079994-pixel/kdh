<div>
    <x-page-header :title="__($data['title'])" :subtitle="__($data['description'])">
        @if($data['ranged'])
            <flux:select wire:model.live="period" size="sm" class="min-w-40">
                <flux:select.option value="today">{{ __('Today') }}</flux:select.option>
                <flux:select.option value="yesterday">{{ __('Yesterday') }}</flux:select.option>
                <flux:select.option value="week">{{ __('This week') }}</flux:select.option>
                <flux:select.option value="month">{{ __('This month') }}</flux:select.option>
                <flux:select.option value="last_month">{{ __('Last month') }}</flux:select.option>
                <flux:select.option value="year">{{ __('This year') }}</flux:select.option>
                <flux:select.option value="custom">{{ __('Custom') }}</flux:select.option>
            </flux:select>
            @if($period === 'custom')
                <flux:input type="date" wire:model.live="from" size="sm" />
                <flux:input type="date" wire:model.live="to" size="sm" />
            @endif
        @endif
        @php($exportParams = ['report' => $report, 'period' => $period, 'from' => $from, 'to' => $to])
        <flux:dropdown>
            <flux:button icon="arrow-down-tray" size="sm">{{ __('Export') }}</flux:button>
            <flux:menu>
                <flux:menu.item :href="route('reports.export', $exportParams + ['format' => 'csv'])" icon="document-text">CSV</flux:menu.item>
                <flux:menu.item :href="route('reports.export', $exportParams + ['format' => 'xlsx'])" icon="document-text">Excel (XLSX)</flux:menu.item>
                <flux:menu.item :href="route('reports.export', $exportParams + ['format' => 'pdf'])" icon="printer">PDF</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
        <flux:button :href="route('reports.index')" variant="ghost" size="sm" wire:navigate>{{ __('All reports') }}</flux:button>
    </x-page-header>

    @if($data['ranged'])
        <flux:text class="mb-3 text-sm">{{ $start->isSameDay($end) ? $start->format('l, d M Y') : $start->format('d M Y').' – '.$end->format('d M Y') }}</flux:text>
    @else
        <flux:text class="mb-3 text-sm">{{ __('As of :t', ['t' => now()->format('d M Y, h:i A')]) }}</flux:text>
    @endif

    @if(!empty($data['summary']))
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($data['summary'] as [$label, $value])
                <div class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800"><span class="text-zinc-500">{{ __($label) }}:</span> <span class="font-semibold tabular-nums">{{ $value }}</span></div>
            @endforeach
        </div>
    @endif

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-900">
                <tr>@foreach($data['columns'] as $c)<th class="px-3 py-2 text-{{ $c['align'] }} whitespace-nowrap">{{ __($c['label']) }}</th>@endforeach</tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @forelse($data['rows'] as $row)
                    <tr class="{{ in_array($row['status'] ?? '', ['Cancelled', 'Reversed'], true) ? 'text-zinc-400 line-through' : '' }}">
                        @foreach($data['columns'] as $c)
                            <td class="px-3 py-1.5 text-{{ $c['align'] }} {{ $c['type'] === 'money' ? 'tabular-nums' : '' }} whitespace-nowrap">{{ $c['type'] === 'money' ? money($row[$c['key']] ?? 0) : ($row[$c['key']] ?? '') }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($data['columns']) }}" class="px-3 py-8 text-center text-zinc-500">{{ __('Nothing to show for this period.') }}</td></tr>
                @endforelse
            </tbody>
            @if($data['totals'])
                <tfoot class="bg-zinc-50 font-semibold dark:bg-zinc-900">
                    <tr>@foreach($data['columns'] as $c)<td class="px-3 py-2 text-{{ $c['align'] }} tabular-nums">{{ isset($data['totals'][$c['key']]) ? ($c['type'] === 'money' ? money($data['totals'][$c['key']]) : $data['totals'][$c['key']]) : '' }}</td>@endforeach</tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
