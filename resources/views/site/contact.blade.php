@extends('site.layout', ['pageTitle' => __('Contact')])

@section('content')
    <section>
        <div class="container grid-2">
            <div>
                <h1>{{ __('Contact us') }}</h1>
                <div class="info-list">
                    @if($business['phone'])<div class="row"><b>{{ __('Phone') }}</b><a href="tel:{{ preg_replace('/\s+/', '', $business['phone']) }}">{{ $business['phone'] }}</a></div>@endif
                    @if($business['whatsapp'])<div class="row"><b>{{ __('WhatsApp') }}</b><a href="https://wa.me/91{{ preg_replace('/\D/', '', ltrim($business['whatsapp'], '+91')) }}" target="_blank" rel="noopener">{{ $business['whatsapp'] }}</a></div>@endif
                    @if($business['email'])<div class="row"><b>{{ __('Email') }}</b><a href="mailto:{{ $business['email'] }}">{{ $business['email'] }}</a></div>@endif
                    @if($business['address'] || $business['city'])<div class="row"><b>{{ __('Address') }}</b><span>{{ collect([$business['address'], $business['city'], $business['state'], $business['pincode']])->filter()->join(', ') }}</span></div>@endif
                    @if(($site['show_opening_hours'] ?? true) && $business['opening_hours'])<div class="row"><b>{{ __('Hours') }}</b><span class="hours">{{ $business['opening_hours'] }}</span></div>@endif
                    @if(($site['show_map'] ?? true) && $business['map_url'])<div class="row"><b>{{ __('Map') }}</b><a href="{{ $business['map_url'] }}" target="_blank" rel="noopener">{{ __('Open in Google Maps') }}</a></div>@endif
                </div>
            </div>
            <div class="card">
                <h3>{{ __('Send an enquiry') }}</h3>
                @if(session('enquiry_sent'))
                    <div class="alert">{{ __('Thank you! We have received your enquiry and will contact you soon.') }}</div>
                @else
                    <form method="POST" action="{{ route('site.enquiry') }}" class="form">
                        @csrf
                        <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>
                        <div>
                            <label for="name">{{ __('Your name') }}</label>
                            <input id="name" name="name" value="{{ old('name') }}" required maxlength="150">
                            @error('name')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="phone">{{ __('Mobile number') }}</label>
                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required inputmode="numeric">
                            @error('phone')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="email">{{ __('Email (optional)') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}">
                            @error('email')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="service_interest">{{ __('Service you need (optional)') }}</label>
                            <input id="service_interest" name="service_interest" value="{{ old('service_interest') }}" maxlength="150">
                        </div>
                        <div>
                            <label for="message">{{ __('Message') }}</label>
                            <textarea id="message" name="message" rows="4" required maxlength="2000">{{ old('message') }}</textarea>
                            @error('message')<div class="error">{{ $message }}</div>@enderror
                        </div>
                        <p class="muted" style="font-size:.85rem;margin:0">{{ __('Please do not send passwords, OTPs, PINs or bank details through this form.') }}</p>
                        <button type="submit" class="btn btn-gold">{{ __('Send enquiry') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
