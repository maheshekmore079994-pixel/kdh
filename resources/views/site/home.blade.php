@extends('site.layout')

@section('content')
    <div class="tricolor"></div>
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <h1>{{ $site['hero_title'] ?: $business['name'] }}</h1>
                <p class="lead">{{ $site['hero_subtitle'] ?: __('Xerox, printing, scanning, online applications, government services and more — all under one roof.') }}</p>
                <div class="actions">
                    <a href="{{ route('site.services') }}" class="btn btn-gold">{{ __('Our services') }}</a>
                    <a href="{{ route('site.contact') }}" class="btn btn-outline">{{ __('Contact us') }}</a>
                </div>
            </div>
            <img src="{{ asset('images/brand/kdh-logo.png') }}" alt="{{ $business['name'] }}" class="hero-logo" width="420" height="254">
        </div>
    </section>

    <section>
        <div class="container">
            <div class="section-head">
                <h2>{{ __('What we do') }}</h2>
                <a href="{{ route('site.services') }}">{{ __('All services →') }}</a>
            </div>
            <div class="grid-3">
                @forelse($categories as $category)
                    <div class="card">
                        <div class="icon">{{ mb_substr($category->name, 0, 1) }}</div>
                        <h3>{{ $category->name }}</h3>
                        @if($category->description)<p class="muted">{{ $category->description }}</p>@endif
                        @if($category->services->isNotEmpty())
                            <ul>
                                @foreach($category->services->take(4) as $service)
                                    <li>{{ $service->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <p class="muted">{{ __('Service details will be published soon. Please contact us for assistance.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="alt">
        <div class="container grid-2">
            <div>
                <h2>{{ __('Visit or call us') }}</h2>
                <div class="info-list">
                    @if($business['phone'])<div class="row"><b>{{ __('Phone') }}</b><a href="tel:{{ preg_replace('/\s+/', '', $business['phone']) }}">{{ $business['phone'] }}</a></div>@endif
                    @if($business['email'])<div class="row"><b>{{ __('Email') }}</b><a href="mailto:{{ $business['email'] }}">{{ $business['email'] }}</a></div>@endif
                    @if($business['address'] || $business['city'])<div class="row"><b>{{ __('Address') }}</b><span>{{ collect([$business['address'], $business['city'], $business['state'], $business['pincode']])->filter()->join(', ') }}</span></div>@endif
                    @if(($site['show_opening_hours'] ?? true) && $business['opening_hours'])<div class="row"><b>{{ __('Hours') }}</b><span class="hours">{{ $business['opening_hours'] }}</span></div>@endif
                </div>
                <div class="actions" style="margin-top:18px">
                    <a href="{{ route('site.contact') }}" class="btn btn-dark">{{ __('Send an enquiry') }}</a>
                    @if(($site['show_map'] ?? true) && $business['map_url'])<a href="{{ $business['map_url'] }}" target="_blank" rel="noopener" class="btn btn-outline">{{ __('Open in Maps') }}</a>@endif
                </div>
            </div>
            <div class="card">
                <h3>{{ __('How it works') }}</h3>
                <ol style="padding-left:18px;color:var(--muted)">
                    <li>{{ __('Bring your documents or send them on WhatsApp.') }}</li>
                    <li>{{ __('We confirm the work and the charge before starting.') }}</li>
                    <li>{{ __('Collect your prints or application receipt.') }}</li>
                </ol>
                <p class="muted" style="font-size:.9rem">{{ __('Charges depend on the service, paper size and quantity. Please ask at the counter or send an enquiry.') }}</p>
            </div>
        </div>
    </section>
@endsection
