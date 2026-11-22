@extends('site.layout', ['pageTitle' => $pageTitle])

@section('content')
    <section>
        <div class="container">
            <h1>{{ $pageTitle }}</h1>
            @if($content)
                <div class="prose">{{ $content }}</div>
            @elseif($kind === 'privacy')
                <div class="prose">{{ __("This website is operated by :name. We collect only the information you choose to send through the enquiry form (name, mobile number, optional email and your message) so that we can respond to you. We do not sell or share this information with third parties, and we never ask for passwords, OTPs, PINs or bank details. Documents you bring to our shop for printing or applications are handled only for the requested work. You may ask us to delete your enquiry at any time by contacting :email.", ['name' => $business['name'], 'email' => $business['email']]) }}</div>
            @else
                <div class="prose">{{ __("Services are provided by :name at the charges confirmed with you before work begins. Please review documents and application details before submission; we are not responsible for errors in information supplied by you or for decisions made by third-party portals or government departments. Completed work should be collected within a reasonable time. For questions, contact :email.", ['name' => $business['name'], 'email' => $business['email']]) }}</div>
            @endif
        </div>
    </section>
@endsection
