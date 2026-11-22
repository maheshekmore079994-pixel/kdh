<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($businessName = $business['name'] ?: config('app.name'))
    <title>{{ isset($pageTitle) ? $pageTitle.' · '.$businessName : $businessName.' — '.($business['tagline'] ?: __('Printing, Xerox & Online Services')) }}</title>
    <meta name="description" content="{{ $metaDescription ?? ($site['meta_description'] ?: $businessName.' — '.($business['tagline'] ?: 'Xerox, printing, scanning, online forms and digital services.')) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ $pageTitle ?? $businessName }}">
    <meta property="og:description" content="{{ $metaDescription ?? ($site['meta_description'] ?: $business['tagline']) }}">
    <meta property="og:image" content="{{ asset('images/brand/kdh-icon-1024.png') }}">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/kdh-icon-1024.png') }}">
    @vite(['resources/css/site.css'])
    <script type="application/ld+json">
    {!! json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $businessName,
        'url' => url('/'),
        'telephone' => $business['phone'] ?: null,
        'email' => $business['email'] ?: null,
        'image' => asset('images/brand/kdh-logo.png'),
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $business['address'] ?: null,
            'addressLocality' => $business['city'] ?: null,
            'addressRegion' => $business['state'] ?: null,
            'postalCode' => $business['pincode'] ?: null,
            'addressCountry' => 'IN',
        ]),
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
</head>
<body class="site">
    <header class="site-header">
        <div class="container nav-row">
            <a href="{{ route('home') }}" class="brand" aria-label="{{ $businessName }}">
                <img src="{{ asset('images/brand/kdh-logo-600.png') }}" alt="{{ $businessName }}" width="180" height="109">
            </a>
            <input type="checkbox" id="nav-toggle" class="nav-toggle" aria-label="{{ __('Open menu') }}">
            <label for="nav-toggle" class="nav-burger" aria-hidden="true"><span></span><span></span><span></span></label>
            <nav class="nav-links">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>{{ __('Home') }}</a>
                <a href="{{ route('site.services') }}" @class(['active' => request()->routeIs('site.services')])>{{ __('Services') }}</a>
                <a href="{{ route('site.about') }}" @class(['active' => request()->routeIs('site.about')])>{{ __('About') }}</a>
                <a href="{{ route('site.contact') }}" @class(['active' => request()->routeIs('site.contact')])>{{ __('Contact') }}</a>
                @if($business['phone'])
                    <a href="tel:{{ preg_replace('/\s+/', '', $business['phone']) }}" class="btn btn-gold">{{ __('Call') }} {{ $business['phone'] }}</a>
                @endif
            </nav>
        </div>
    </header>

    <main>
        @if(!($site['enabled'] ?? true))
            <section class="hero">
                <div class="container">
                    <h1>{{ $businessName }}</h1>
                    <p class="lead">{{ __('Our website is coming soon. Contact us directly:') }}</p>
                    <p>@if($business['phone'])<a href="tel:{{ $business['phone'] }}">{{ $business['phone'] }}</a> · @endif<a href="mailto:{{ $business['email'] }}">{{ $business['email'] }}</a></p>
                </div>
            </section>
        @else
            @yield('content')
        @endif
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <img src="{{ asset('images/brand/kdh-logo-600.png') }}" alt="{{ $businessName }}" width="160" height="97" class="footer-logo">
                @if($business['tagline'])<p class="muted">{{ $business['tagline'] }}</p>@endif
            </div>
            <div>
                <h4>{{ __('Contact') }}</h4>
                <ul class="plain">
                    @if($business['phone'])<li><a href="tel:{{ preg_replace('/\s+/', '', $business['phone']) }}">{{ $business['phone'] }}</a></li>@endif
                    @if($business['email'])<li><a href="mailto:{{ $business['email'] }}">{{ $business['email'] }}</a></li>@endif
                    @if($business['address'] || $business['city'])<li>{{ collect([$business['address'], $business['city'], $business['state'], $business['pincode']])->filter()->join(', ') }}</li>@endif
                </ul>
            </div>
            <div>
                <h4>{{ __('Links') }}</h4>
                <ul class="plain">
                    <li><a href="{{ route('site.services') }}">{{ __('Services') }}</a></li>
                    <li><a href="{{ route('site.privacy') }}">{{ __('Privacy policy') }}</a></li>
                    <li><a href="{{ route('site.terms') }}">{{ __('Terms') }}</a></li>
                    <li><a href="{{ route('login') }}">{{ __('Staff login') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="container copyright">© {{ date('Y') }} {{ $businessName }}. {{ __('All rights reserved.') }}</div>
    </footer>

    @if($business['whatsapp'])
        <a class="whatsapp-fab" href="https://wa.me/91{{ preg_replace('/\D/', '', ltrim($business['whatsapp'], '+91')) }}" target="_blank" rel="noopener" aria-label="{{ __('Chat on WhatsApp') }}">
            <svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M16 3C9.4 3 4 8.3 4 14.8c0 2.4.7 4.6 2 6.5L4 29l8-2c1.3.5 2.6.7 4 .7 6.6 0 12-5.3 12-11.9S22.6 3 16 3zm0 21.6c-1.3 0-2.5-.3-3.6-.8l-.3-.1-4.7 1.2 1.3-4.5-.2-.3a9.5 9.5 0 0 1-1.6-5.3C6.9 9.6 11 5.6 16 5.6s9.1 4 9.1 9.2-4.1 9.8-9.1 9.8zm5-7.3c-.3-.1-1.6-.8-1.9-.9-.3-.1-.4-.1-.6.1-.2.3-.7.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.4-2.2-1.4-.8-.7-1.4-1.6-1.5-1.9-.2-.3 0-.4.1-.6l.4-.5.3-.5c.1-.2 0-.4 0-.5l-.9-2c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.3.3-1 .9-1 2.3s1 2.7 1.1 2.9c.1.2 2 3 4.8 4.2 1.8.8 2.4.8 3.2.7.5-.1 1.6-.7 1.8-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.5-.3z"/></svg>
        </a>
    @endif
</body>
</html>
