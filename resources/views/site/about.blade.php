@extends('site.layout', ['pageTitle' => __('About')])

@section('content')
    <section>
        <div class="container grid-2">
            <div>
                <h1>{{ __('About :name', ['name' => $business['name']]) }}</h1>
                @if($site['about'])
                    <div class="prose">{{ $site['about'] }}</div>
                @else
                    <p class="lead">{{ $business['tagline'] ?: __('A local digital services centre.') }}</p>
                    <p class="muted">{{ __('We provide Xerox, printing, scanning, typing, online application and government service assistance for our community. Visit us or get in touch for help with your documents.') }}</p>
                @endif
            </div>
            <div>
                <img src="{{ asset('images/brand/kdh-logo.png') }}" alt="{{ $business['name'] }}" class="hero-logo">
            </div>
        </div>
    </section>
@endsection
