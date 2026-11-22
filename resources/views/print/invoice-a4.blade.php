<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $invoice->invoice_number }}</title>
<style>
    @page { size: A4; margin: 14mm; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: {{ isset($pdf) ? 'DejaVu Sans, sans-serif' : 'Segoe UI, Arial, sans-serif' }}; font-size: 12px; color: #111; }
    .sheet { max-width: 190mm; margin: 0 auto; }
    .top { display: table; width: 100%; border-bottom: 2px solid #d59a2f; padding-bottom: 8px; margin-bottom: 14px; }
    .top > div { display: table-cell; vertical-align: top; }
    .biz .logo { width: 150px; margin-bottom: 6px; }
    .biz-name { font-size: 18px; font-weight: bold; }
    .biz-tag { color: #555; }
    .biz-line { color: #333; font-size: 11px; }
    .inv-meta { text-align: right; }
    .inv-meta h1 { margin: 0 0 6px; font-size: 22px; letter-spacing: .05em; }
    .inv-meta div { margin: 2px 0; }
    .parties { margin-bottom: 12px; }
    .box { border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; }
    table.items { width: 100%; border-collapse: collapse; }
    table.items th { background: #111; color: #fff; text-align: left; padding: 6px 8px; font-size: 11px; }
    table.items td { padding: 6px 8px; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
    .r { text-align: right; }
    .totals { width: 260px; margin-left: auto; margin-top: 10px; border-collapse: collapse; }
    .totals td { padding: 4px 8px; }
    .totals .grand td { border-top: 2px solid #111; font-weight: bold; font-size: 14px; }
    .terms { margin-top: 18px; font-size: 10.5px; color: #555; white-space: pre-line; }
    .foot { margin-top: 28px; display: table; width: 100%; }
    .foot > div { display: table-cell; vertical-align: bottom; }
    .sign { text-align: right; padding-top: 30px; }
    .cancel { color: #b00; font-weight: bold; font-size: 16px; border: 2px solid #b00; display: inline-block; padding: 4px 10px; }
    @media screen { body { background: #e5e5e5; } .sheet { background: #fff; padding: 14mm; margin: 12px auto; box-shadow: 0 2px 10px rgba(0,0,0,.2); } }
</style>
</head>
<body>
<div class="sheet">
    <div class="top">
        <div style="width:55%">@include('print.partials.business-header')</div>
        <div class="inv-meta">
            <h1>{{ __('INVOICE') }}</h1>
            <div><b>{{ __('No.') }}</b> {{ $invoice->invoice_number }}</div>
            <div><b>{{ __('Date') }}</b> {{ $invoice->invoice_date->format('d M Y') }}</div>
            <div><b>{{ __('Status') }}</b> {{ $invoice->status->label() }}</div>
            @if($invoice->isCancelled())<div class="cancel" style="margin-top:6px">{{ __('CANCELLED') }}</div>@endif
        </div>
    </div>

    <div class="parties">
        <div class="box">
            <b>{{ __('Bill to') }}</b><br>
            @if($invoice->customer)
                {{ $invoice->customer->name }}<br>{{ __('Mobile') }}: {{ $invoice->customer->mobile }}
                @if($invoice->customer->address)<br>{{ $invoice->customer->address }}@endif
            @else
                {{ __('Walk-in customer') }}
            @endif
        </div>
    </div>

    <table class="items">
        <thead><tr><th style="width:30px">#</th><th>{{ __('Description') }}</th><th class="r">{{ __('Qty') }}</th><th class="r">{{ __('Rate (₹)') }}</th><th class="r">{{ __('Discount') }}</th><th class="r">{{ __('Amount (₹)') }}</th></tr></thead>
        <tbody>
            @foreach($invoice->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td class="r">{{ qty($item->quantity) }} {{ $item->unit }}</td>
                    <td class="r">{{ money($item->unit_price, false) }}</td>
                    <td class="r">{{ bccomp($item->discount_amount, '0', 2) > 0 ? money($item->discount_amount, false) : '—' }}</td>
                    <td class="r">{{ money($item->line_total, false) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>{{ __('Subtotal') }}</td><td class="r">{{ money($invoice->subtotal) }}</td></tr>
        @if(bccomp($invoice->discount_amount, '0', 2) > 0)<tr><td>{{ __('Discount') }}</td><td class="r">−{{ money($invoice->discount_amount) }}</td></tr>@endif
        @if($invoice->tax_rate !== null)<tr><td>{{ $taxLabel }} @ {{ $invoice->tax_rate }}%</td><td class="r">{{ money($invoice->tax_amount) }}</td></tr>@endif
        <tr class="grand"><td>{{ __('Total') }}</td><td class="r">{{ money($invoice->total) }}</td></tr>
        <tr><td>{{ __('Paid') }}</td><td class="r">{{ money($invoice->paid_amount) }}</td></tr>
        <tr><td><b>{{ __('Balance due') }}</b></td><td class="r"><b>{{ money($invoice->balance_due) }}</b></td></tr>
    </table>

    @if($invoice->payments->whereNull('reversed_at')->isNotEmpty())
        <div style="margin-top:10px;font-size:11px"><b>{{ __('Payments') }}:</b>
            @foreach($invoice->payments->whereNull('reversed_at') as $p)
                {{ $p->paid_at->format('d M Y') }} · {{ $p->method?->name }} · {{ money($p->amount) }}@if($p->reference) ({{ $p->reference }})@endif@if(!$loop->last); @endif
            @endforeach
        </div>
    @endif

    @if($invoice->notes)<div style="margin-top:10px"><b>{{ __('Notes') }}:</b> {{ $invoice->notes }}</div>@endif
    @if(!empty($invoiceSettings['terms']))<div class="terms"><b>{{ __('Terms') }}</b>{{ "\n" }}{{ $invoiceSettings['terms'] }}</div>@endif

    <div class="foot">
        <div>{{ $invoiceSettings['footer_note'] ?? '' }}</div>
        <div class="sign">{{ __('For') }} {{ $business['name'] }}<br><br>{{ __('Authorised signatory') }}</div>
    </div>
</div>
@if($auto)<script>window.addEventListener('load', () => { window.print(); });</script>@endif
</body>
</html>
