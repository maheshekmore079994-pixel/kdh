<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $data['title'] }}</title>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    .meta { color: #555; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #111; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; }
    td { padding: 4px 6px; border-bottom: 1px solid #e5e5e5; }
    .end { text-align: right; }
    tfoot td { font-weight: bold; border-top: 2px solid #111; }
    .summary { margin-top: 10px; }
    .summary span { display: inline-block; margin-right: 14px; }
    .foot { margin-top: 14px; color: #777; font-size: 9px; }
</style>
</head>
<body>
    <h1>{{ $business['name'] }} — {{ $data['title'] }}</h1>
    <div class="meta">
        @if($data['ranged']){{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}@else As of {{ now()->format('d M Y, h:i A') }}@endif
        · {{ $data['description'] }}
    </div>
    <table>
        <thead><tr>@foreach($data['columns'] as $c)<th class="{{ $c['align'] }}">{{ $c['label'] }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse($data['rows'] as $row)
                <tr>@foreach($data['columns'] as $c)<td class="{{ $c['align'] }}">{{ $c['type'] === 'money' ? money($row[$c['key']] ?? 0) : ($row[$c['key']] ?? '') }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($data['columns']) }}">Nothing to show.</td></tr>
            @endforelse
        </tbody>
        @if($data['totals'])
            <tfoot><tr>@foreach($data['columns'] as $c)<td class="{{ $c['align'] }}">{{ isset($data['totals'][$c['key']]) ? ($c['type'] === 'money' ? money($data['totals'][$c['key']]) : $data['totals'][$c['key']]) : '' }}</td>@endforeach</tr></tfoot>
        @endif
    </table>
    @if(!empty($data['summary']))
        <div class="summary">@foreach($data['summary'] as [$label, $value])<span><b>{{ $label }}:</b> {{ $value }}</span>@endforeach</div>
    @endif
    <div class="foot">Generated {{ now()->format('d M Y, h:i A') }} by {{ auth()->user()?->name }}.</div>
</body>
</html>
