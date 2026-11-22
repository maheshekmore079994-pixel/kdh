<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $invoice->invoice_number }}</title>
@php($width = ($invoiceSettings['receipt_width'] ?? '80mm') === '58mm' ? '58mm' : '80mm')
<style>
    @page { margin: 0; size: {{ $width }} auto; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: {{ isset($pdf) ? 'DejaVu Sans, sans-serif' : "'Courier New', ui-monospace, monospace" }}; font-size: {{ $width === '58mm' ? '10px' : '12px' }}; color: #000; width: {{ $width }}; }
    .ticket { padding: 4mm 3mm; }
    .biz { text-align: center; }
    .biz .logo { width: 60%; margin: 0 auto 2mm; }
    .biz-name { font-weight: bold; font-size: 1.25em; }
    .biz-tag, .biz-line { font-size: .9em; }
    .meta { margin: 3mm 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 2mm 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 1px 0; vertical-align: top; }
    th { text-align: left; border-bottom: 1px dashed #000; font-size: .9em; }
    .r { text-align: right; }
    .totals td { padding: 1px 0; }
    .grand { font-weight: bold; font-size: 1.15em; border-top: 1px dashed #000; }
    .foot { text-align: center; margin-top: 3mm; font-size: .9em; }
    .cancel { text-align: center; font-weight: bold; border: 2px solid #000; padding: 2mm; margin: 2mm 0; }
    @media screen { body { background: #e5e5e5; } .ticket { background: #fff; margin: 10px auto; box-shadow: 0 2px 10px rgba(0,0,0,.2); } }
</style>
</head>
<body>
<div class="ticket">
    @include('print.partials.business-header', ['compact' => false])

    <div class="meta">
        <div><b>{{ __('Bill') }}:</b> {{ $invoice->invoice_number }}</div>
        <div><b>{{ __('Date') }}:</b> {{ $invoice->invoice_date->format('d-m-Y') }} {{ $invoice->created_at->format('h:i A') }}</div>
        @if($invoice->customer)<div><b>{{ __('Customer') }}:</b> {{ $invoice->customer->name }} ({{ $invoice->customer->mobile }})</div>@endif
        <div><b>{{ __('Billed by') }}:</b> {{ $invoice->creator?->name }}</div>
    </div>

    @if($invoice->isCancelled())<div class="cancel">{{ __('CANCELLED') }}</div>@endif

    <table>
        <thead><tr><th>{{ __('Item') }}</th><th class="r">{{ __('Qty') }}</th><th class="r">{{ __('Rate') }}</th><th class="r">{{ __('Amt') }}</th></tr></thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr><td colspan="4">{{ $item->description }}</td></tr>
                <tr><td></td><td class="r">{{ qty($item->quantity) }}</td><td class="r">{{ money($item->unit_price, false) }}</td><td class="r">{{ money($item->line_total, false) }}</td></tr>
                @if(bccomp($item->discount_amount, '0', 2) > 0)<tr><td colspan="3" class="r">{{ __('less disc.') }}</td><td class="r">-{{ money($item->discount_amount, false) }}</td></tr>@endif
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top:2mm">
        <tr><td>{{ __('Subtotal') }}</td><td class="r">{{ money($invoice->subtotal, false) }}</td></tr>
        @if(bccomp($invoice->discount_amount, '0', 2) > 0)<tr><td>{{ __('Discount') }}</td><td class="r">-{{ money($invoice->discount_amount, false) }}</td></tr>@endif
        @if($invoice->tax_rate !== null)<tr><td>{{ $taxLabel }} {{ $invoice->tax_rate }}%</td><td class="r">{{ money($invoice->tax_amount, false) }}</td></tr>@endif
        <tr class="grand"><td>{{ __('TOTAL') }}</td><td class="r">{{ money($invoice->total) }}</td></tr>
        @foreach($invoice->payments->whereNull('reversed_at') as $p)
            <tr><td>{{ __('Paid') }} ({{ $p->method?->name }})</td><td class="r">{{ money($p->amount, false) }}</td></tr>
        @endforeach
        @if(bccomp($invoice->balance_due, '0', 2) > 0)<tr class="grand"><td>{{ __('BALANCE DUE') }}</td><td class="r">{{ money($invoice->balance_due) }}</td></tr>@endif
    </table>

    @if($invoice->notes)<div style="margin-top:2mm;font-size:.9em">{{ $invoice->notes }}</div>@endif
    <div class="foot">{{ $invoiceSettings['footer_note'] ?? '' }}</div>
</div>
@if($auto)<script>window.addEventListener('load', () => { window.print(); });</script>@endif
</body>
</html>
