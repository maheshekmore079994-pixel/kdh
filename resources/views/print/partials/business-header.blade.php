@php($addressLine = collect([$business['address'] ?? null, $business['city'] ?? null, $business['state'] ?? null, $business['pincode'] ?? null])->filter()->join(', '))
<div class="biz">
    @if(($invoiceSettings['show_logo'] ?? true) && !($compact ?? false))
        <img src="{{ $logoUrl ?? public_path('images/brand/kdh-logo.png') }}" alt="" class="logo">
    @endif
    <div class="biz-name">{{ $business['name'] }}</div>
    @if($business['tagline'])<div class="biz-tag">{{ $business['tagline'] }}</div>@endif
    @if($addressLine)<div class="biz-line">{{ $addressLine }}</div>@endif
    <div class="biz-line">
        @if($business['phone']){{ __('Ph') }}: {{ $business['phone'] }}@endif
        @if($business['phone'] && $business['email']) · @endif
        @if($business['email']){{ $business['email'] }}@endif
    </div>
    @if($business['gstin'])<div class="biz-line">GSTIN: {{ $business['gstin'] }}</div>@endif
</div>
