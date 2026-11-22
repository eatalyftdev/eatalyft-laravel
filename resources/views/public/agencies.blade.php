@extends('layouts.public')

@section('title', __('Agencies - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('Partner Agencies') }}</h1>
        <p>{{ __('All our partners are KYC-verified for your safety.') }}</p>
    </div>
</header>

<section class="services-grid" aria-labelledby="agencies-heading">
    <div class="container">
        <h2 id="agencies-heading" class="sr-only">{{ __('Verified agencies') }}</h2>
        
        @forelse($agencies ?? [] as $agency)
            <article class="service-card">
                @if($agency->logo_url)
                    <x-eatalyft.image
                        src="{{ $agency->logo_url }}"
                        manifestKey="placeholders.operator_logo"
                        alt="{{ $agency->name }} logo"
                        width="64"
                        height="64"
                        class="icon"
                        objectFit="contain"
                    />
                @else
                    <x-eatalyft.image
                        manifestKey="placeholders.operator_logo"
                        alt="{{ $agency->name }} logo"
                        width="64"
                        height="64"
                        class="icon"
                        objectFit="contain"
                    />
                @endif
                <h3>{{ $agency->name }}</h3>
                <p>{{ $agency->description ?? __('Verified partner agency.') }}</p>
                @if($agency->city)
                    <p style="font-size: var(--text-xs); color: var(--color-muted); margin-top: var(--space-2);">{{ $agency->city }}</p>
                @endif
            </article>
        @empty
            <div class="service-card" style="grid-column: 1 / -1; text-align: center;">
                <p>{{ __('No agencies listed yet.') }}</p>
            </div>
        @endforelse
    </div>
</section>

<section class="final-cta" aria-labelledby="agency-cta-heading">
    <div class="container">
        <h2 id="agency-cta-heading">{{ __('Want to become a partner?') }}</h2>
        <p>{{ __('Join our network of verified agencies and reach more customers.') }}</p>
        <a href="{{ route('contact') }}?type=partnership" class="cta-pill">{{ __('Apply now') }}</a>
    </div>
</section>
@endsection