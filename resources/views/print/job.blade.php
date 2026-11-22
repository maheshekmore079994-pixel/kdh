<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $job->job_number }}</title>
<style>
    @page { size: A5; margin: 10mm; }
    body { margin: 0; font-family: {{ isset($pdf) ? 'DejaVu Sans, sans-serif' : 'Segoe UI, Arial, sans-serif' }}; font-size: 12px; color: #111; }
    .sheet { max-width: 128mm; margin: 0 auto; }
    .biz { text-align: center; border-bottom: 2px solid #d59a2f; padding-bottom: 6px; margin-bottom: 10px; }
    .biz .logo { width: 120px; margin: 0 auto 4px; }
    .biz-name { font-weight: bold; font-size: 16px; }
    .biz-tag, .biz-line { font-size: 11px; color: #444; }
    h2 { text-align: center; margin: 6px 0 10px; letter-spacing: .08em; font-size: 14px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 3px 0; vertical-align: top; }
    td.k { color: #555; width: 38%; }
    .box { border: 1px solid #ccc; border-radius: 4px; padding: 8px; margin-top: 10px; }
    .big { font-size: 16px; font-weight: bold; }
    .foot { margin-top: 14px; font-size: 10px; color: #555; text-align: center; }
    @media screen { body { background: #e5e5e5; } .sheet { background: #fff; padding: 10mm; margin: 12px auto; box-shadow: 0 2px 10px rgba(0,0,0,.2); } }
</style>
</head>
<body>
<div class="sheet">
    @include('print.partials.business-header')
    <h2>{{ __('JOB RECEIPT') }}</h2>
    <table>
        <tr><td class="k">{{ __('Job no.') }}</td><td class="big">{{ $job->job_number }}</td></tr>
        <tr><td class="k">{{ __('Customer') }}</td><td>{{ $job->customer->name }} · {{ $job->customer->mobile }}</td></tr>
        <tr><td class="k">{{ __('Work') }}</td><td>{{ $job->title }}</td></tr>
        @if($job->description)<tr><td class="k">{{ __('Details') }}</td><td>{{ $job->description }}</td></tr>@endif
        <tr><td class="k">{{ __('Quantity') }}</td><td>{{ qty($job->quantity) }} × {{ money($job->unit_price) }}</td></tr>
        <tr><td class="k">{{ __('Amount') }}</td><td class="big">{{ money($job->total_amount) }}</td></tr>
        <tr><td class="k">{{ __('Received on') }}</td><td>{{ $job->received_at->format('d M Y, h:i A') }}</td></tr>
        <tr><td class="k">{{ __('Expected delivery') }}</td><td>{{ $job->expected_at?->format('d M Y, h:i A') ?? __('To be confirmed') }}</td></tr>
        <tr><td class="k">{{ __('Status') }}</td><td>{{ $job->status->label() }}</td></tr>
    </table>
    <div class="box">
        @if($job->invoice)
            <table>
                <tr><td class="k">{{ __('Invoice') }}</td><td>{{ $job->invoice->invoice_number }}</td></tr>
                <tr><td class="k">{{ __('Advance paid') }}</td><td>{{ money($job->invoice->paid_amount) }}</td></tr>
                <tr><td class="k">{{ __('Balance due') }}</td><td class="big">{{ money($job->invoice->balance_due) }}</td></tr>
            </table>
        @else
            {{ __('Payment to be collected on delivery.') }}
        @endif
    </div>
    <div class="foot">{{ __('Please bring this receipt when collecting your work.') }} {{ $invoiceSettings['footer_note'] ?? '' }}</div>
</div>
@if($auto)<script>window.addEventListener('load', () => { window.print(); });</script>@endif
</body>
</html>
