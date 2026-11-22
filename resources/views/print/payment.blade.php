<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $payment->payment_number }}</title>
<style>
    @page { margin: 0; size: 80mm auto; }
    body { margin: 0; font-family: 'Courier New', ui-monospace, monospace; font-size: 12px; width: 80mm; }
    .ticket { padding: 4mm 3mm; }
    .biz { text-align: center; }
    .biz-name { font-weight: bold; font-size: 1.25em; }
    .biz-tag, .biz-line { font-size: .9em; }
    .meta { margin: 3mm 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 2mm 0; }
    .amt { text-align: center; font-size: 1.4em; font-weight: bold; margin: 3mm 0; }
    .foot { text-align: center; margin-top: 3mm; font-size: .9em; }
    @media screen { body { background: #e5e5e5; } .ticket { background: #fff; margin: 10px auto; box-shadow: 0 2px 10px rgba(0,0,0,.2); } }
</style>
</head>
<body>
<div class="ticket">
    @include('print.partials.business-header', ['invoiceSettings' => ['show_logo' => false]])
    <div class="meta">
        <div style="text-align:center;font-weight:bold">{{ __('PAYMENT RECEIPT') }}</div>
        <div><b>{{ __('Receipt') }}:</b> {{ $payment->payment_number }}</div>
        <div><b>{{ __('Date') }}:</b> {{ $payment->paid_at->format('d-m-Y h:i A') }}</div>
        <div><b>{{ __('Against bill') }}:</b> {{ $payment->invoice?->invoice_number }}</div>
        @if($payment->invoice?->customer)<div><b>{{ __('From') }}:</b> {{ $payment->invoice->customer->name }} ({{ $payment->invoice->customer->mobile }})</div>@endif
        <div><b>{{ __('Mode') }}:</b> {{ $payment->method?->name }}@if($payment->reference) · {{ $payment->reference }}@endif</div>
        <div><b>{{ __('Received by') }}:</b> {{ $payment->receiver?->name }}</div>
    </div>
    <div class="amt">{{ money($payment->amount) }}</div>
    @if($payment->isReversed())<div style="text-align:center;border:2px solid #000;padding:2mm;font-weight:bold">{{ __('REVERSED') }}</div>@endif
    <div>{{ __('Bill total') }}: {{ money($payment->invoice?->total) }} · {{ __('Balance') }}: {{ money($payment->invoice?->balance_due) }}</div>
    <div class="foot">{{ __('Thank you') }}</div>
</div>
@if($auto)<script>window.addEventListener('load', () => { window.print(); });</script>@endif
</body>
</html>
