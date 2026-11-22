@extends('site.layout', ['pageTitle' => __('Services')])

@section('content')
    <section>
        <div class="container">
            <h1>{{ __('Services') }}</h1>
            <p class="lead">{{ __('Everything we offer at :name. Prices depend on paper size, colour and quantity — ask us for a quote.', ['name' => $business['name']]) }}</p>
            <div class="grid-2" style="margin-top:28px">
                @forelse($categories as $category)
                    <div class="card">
                        <h3>{{ $category->name }}</h3>
                        @if($category->description)<p class="muted">{{ $category->description }}</p>@endif
                        <ul>
                            @foreach($category->services as $service)
                                <li>{{ $service->name }}@if($service->description) <span class="muted">— {{ $service->description }}</span>@endif</li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="muted">{{ __('Service details will be published soon.') }}</p>
                @endforelse
            </div>
            <div class="actions" style="margin-top:28px">
                <a href="{{ route('site.contact') }}" class="btn btn-gold">{{ __('Ask about a service') }}</a>
            </div>
        </div>
    </section>
@endsection
