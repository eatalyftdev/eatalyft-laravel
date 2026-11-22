@extends('layouts.public')

@section('title', __('Services - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('Our Services') }}</h1>
        <p>{{ __('Everything you need for travel and daily life, in one place.') }}</p>
    </div>
</header>

<section class="services-grid" aria-labelledby="services-heading">
    <div class="container">
        <h2 id="services-heading" class="sr-only">{{ __('Available services') }}</h2>
        
        <article class="service-card">
            <div class="icon" aria-hidden="true">🚌</div>
            <h3>{{ __('Bus tickets') }}</h3>
            <p>{{ __('Intercity and local buses with seat selection and instant e-tickets.') }}</p>
        </article>
        
        <article class="service-card">
            <div class="icon" aria-hidden="true">✈️</div>
            <h3>{{ __('Flights') }}</h3>
            <p>{{ __('Domestic and international flights with flexible booking options.') }}</p>
        </article>
        
        <article class="service-card">
            <div class="icon" aria-hidden="true">📶</div>
            <h3>{{ __('eSIM') }}</h3>
            <p>{{ __('Instant data connectivity in 190+ countries. No physical SIM needed.') }}</p>
        </article>
        
        <article class="service-card">
            <div class="icon" aria-hidden="true">🎁</div>
            <h3>{{ __('Gift cards') }}</h3>
            <p>{{ __('Give the gift of travel. No expiry, usable across all services.') }}</p>
        </article>
        
        <article class="service-card">
            <div class="icon" aria-hidden="true">🏨</div>
            <h3>{{ __('Hotels') }}</h3>
            <p>{{ __('Curated stays with free cancellation on most bookings.') }}</p>
        </article>
        
        <article class="service-card">
            <div class="icon" aria-hidden="true">🔧</div>
            <h3>{{ __('Handyman') }}</h3>
            <p>{{ __('Verified professionals for repairs, installations, and maintenance.') }}</p>
        </article>
        
        <article class="service-card">
            <div class="icon" aria-hidden="true">👶</div>
            <h3>{{ __('Nanny') }}</h3>
            <p>{{ __('Background-checked caregivers available on your schedule.') }}</p>
        </article>
    </div>
</section>

<section class="final-cta" aria-labelledby="cta-heading">
    <div class="container">
        <h2 id="cta-heading">{{ __('Can\'t find what you need?') }}</h2>
        <p>{{ __('More services coming soon. Join the waitlist for early access.') }}</p>
        <a href="{{ route('contact') }}" class="cta-pill">{{ __('Contact us') }}</a>
    </div>
</section>
@endsection