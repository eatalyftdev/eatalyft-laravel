@extends('layouts.public')

@section('title', __('Book Bus Tickets'))
@section('description', __('Discover routes, compare travel options and reserve your bus seat with EataLyft — simpler, smarter bus travel across West Africa.'))

@section('content')
    <!-- Decorative top wave -->
    <div class="ely-deco-top">
        <img src="{{ asset('assets/eatalyft/decorations/top-wave.svg') }}" alt="" class="ely-deco-img" aria-hidden="true">
    </div>

    <!-- Hero -->
    <section class="ely-hero" aria-labelledby="ely-hero-heading">
        <div class="container ely-hero-inner">
            <div class="ely-hero-content">
                <span class="ely-hero-kicker">{{ __('EataLyft · Bus Travel') }}</span>
                <h1 id="ely-hero-heading">{{ __('Your Journey. Your Seat. Your Way.') }}</h1>
                <p class="ely-hero-sub">{{ __('Discover routes, compare travel options and reserve your bus seat with a simpler, smarter booking experience.') }}</p>
                <div class="ely-hero-actions">
                    <a href="{{ route('search') }}?service=bus_tickets" class="ely-btn ely-btn-primary ely-btn-large">{{ __('Explore Bus Tickets') }}</a>
                    <a href="{{ route('about') }}" class="ely-btn ely-btn-outline ely-btn-large">{{ __('How It Works') }}</a>
                </div>
            </div>
            <div class="ely-hero-visual">
                <x-eatalyft.image
                    manifestKey="buses.coach_premium"
                    alt="Intercity coach on a travel route"
                    width="560"
                    height="320"
                    priority
                    objectFit="cover"
                    class="ely-hero-img"
                />
            </div>
        </div>
    </section>

    <!-- Bus Search / Discovery -->
    <section class="ely-search" aria-labelledby="ely-search-heading">
        <div class="container">
            <div class="ely-search-card">
                <form action="{{ route('search') }}" method="GET" class="ely-search-form" id="ely-search-form">
                    <input type="hidden" name="service" value="bus_tickets">
                    <div class="ely-search-row">
                        <div class="ely-search-field">
                            <label for="ely-from">{{ __('From') }}</label>
                            <x-eatalyft.image manifestKey="icons.location-marker" alt="" width="20" height="20" class="ely-search-icon" />
                            <input type="text" name="from" id="ely-from" placeholder="{{ __('Departure city') }}" aria-label="{{ __('Departure city') }}" required>
                        </div>
                        <div class="ely-search-field">
                            <label for="ely-to">{{ __('To') }}</label>
                            <x-eatalyft.image manifestKey="icons.location-marker" alt="" width="20" height="20" class="ely-search-icon" />
                            <input type="text" name="to" id="ely-to" placeholder="{{ __('Destination') }}" aria-label="{{ __('Destination') }}" required>
                        </div>
                        <div class="ely-search-field">
                            <label for="ely-date">{{ __('Date') }}</label>
                            <x-eatalyft.image manifestKey="icons.clock" alt="" width="20" height="20" class="ely-search-icon" />
                            <input type="date" name="date" id="ely-date" aria-label="{{ __('Travel date') }}" required>
                        </div>
                        <div class="ely-search-field ely-search-field-passengers">
                            <label for="ely-passengers">{{ __('Passengers') }}</label>
                            <x-eatalyft.image manifestKey="icons.seat" alt="" width="20" height="20" class="ely-search-icon" />
                            <select name="passengers" id="ely-passengers" aria-label="{{ __('Number of passengers') }}">
                                <option value="1">{{ __('1 passenger') }}</option>
                                <option value="2">{{ __('2 passengers') }}</option>
                                <option value="3">{{ __('3 passengers') }}</option>
                                <option value="4">{{ __('4 passengers') }}</option>
                            </select>
                        </div>
                        <button type="submit" class="ely-btn ely-btn-primary ely-btn-large ely-search-submit">{{ __('Search trips') }}</button>
                    </div>
                    <p class="ely-search-note">{{ __('Search results show available routes and seats. Seat holds are temporary reservations until booking is completed.') }}</p>
                </form>
            </div>
        </div>
    </section>

    <!-- Benefits -->
    <section class="ely-benefits" aria-labelledby="ely-benefits-heading">
        <div class="container">
            <div class="ely-section-header text-center">
                <h2 id="ely-benefits-heading">{{ __('Why travel with EataLyft') }}</h2>
                <p>{{ __('Built for travelers who value simplicity, reliability and a seat they can count on.') }}</p>
            </div>
            <div class="ely-benefits-grid">
                <article class="ely-benefit-card ely-reveal">
                    <div class="ely-benefit-icon">
                        <x-eatalyft.image manifestKey="icons.bus" alt="" width="32" height="32" />
                    </div>
                    <h3>{{ __('Discover bus routes') }}</h3>
                    <p>{{ __('Compare intercity and local routes with real-time seat availability before you book.') }}</p>
                </article>
                <article class="ely-benefit-card ely-reveal">
                    <div class="ely-benefit-icon">
                        <x-eatalyft.image manifestKey="icons.seat" alt="" width="32" height="32" />
                    </div>
                    <h3>{{ __('Choose your seat') }}</h3>
                    <p>{{ __('Pick your preferred seat on the map and hold it while you complete your booking.') }}</p>
                </article>
                <article class="ely-benefit-card ely-reveal">
                    <div class="ely-benefit-icon">
                        <x-eatalyft.image manifestKey="icons.clock" alt="" width="32" height="32" />
                    </div>
                    <h3>{{ __('Simple booking flow') }}</h3>
                    <p>{{ __('Find your route, select your seat, and confirm — no complicated steps.') }}</p>
                </article>
                <article class="ely-benefit-card ely-reveal">
                    <div class="ely-benefit-icon">
                        <x-eatalyft.image manifestKey="icons.check" alt="" width="32" height="32" />
                    </div>
                    <h3>{{ __('Verified agencies') }}</h3>
                    <p>{{ __('Every transport agency is vetted so you travel with confidence.') }}</p>
                </article>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="ely-how-it-works" aria-labelledby="ely-how-heading">
        <div class="container">
            <div class="ely-section-header text-center">
                <h2 id="ely-how-heading">{{ __('How it works') }}</h2>
                <p>{{ __('From search to seat in three simple steps.') }}</p>
            </div>
            <div class="ely-steps-grid">
                <div class="ely-step ely-reveal">
                    <div class="ely-step-number">01</div>
                    <div class="ely-step-icon">
                        <x-eatalyft.image manifestKey="icons.search" alt="" width="36" height="36" />
                    </div>
                    <h3>{{ __('Find your route') }}</h3>
                    <p>{{ __('Enter your departure city, destination and travel date to see available trips.') }}</p>
                </div>
                <div class="ely-step ely-reveal">
                    <div class="ely-step-number">02</div>
                    <div class="ely-step-icon">
                        <x-eatalyft.image manifestKey="icons.seat" alt="" width="36" height="36" />
                    </div>
                    <h3>{{ __('Choose your trip and seat') }}</h3>
                    <p>{{ __('Select your preferred trip, pick your seat on the map and hold it temporarily.') }}</p>
                </div>
                <div class="ely-step ely-reveal">
                    <div class="ely-step-number">03</div>
                    <div class="ely-step-icon">
                        <x-eatalyft.image manifestKey="tickets.digital_ticket" alt="" width="36" height="36" />
                    </div>
                    <h3>{{ __('Complete your booking') }}</h3>
                    <p>{{ __('Confirm your details and complete checkout. Your seat hold becomes a booking once payment is processed.') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Travel Experience Showcase -->
    <section class="ely-showcase" aria-labelledby="ely-showcase-heading">
        <div class="container">
            <div class="ely-showcase-inner">
                <div class="ely-showcase-content ely-reveal">
                    <h2 id="ely-showcase-heading">{{ __('Your journey, simplified') }}</h2>
                    <p>{{ __('From route discovery to seat selection, EataLyft puts you in control of your bus travel experience. No surprises, no complications.') }}</p>
                    <a href="{{ route('search') }}?service=bus_tickets" class="ely-btn ely-btn-primary">{{ __('Start searching') }}</a>
                </div>
                <div class="ely-showcase-visual ely-reveal">
                    <x-eatalyft.image
                        manifestKey="tickets.digital_ticket"
                        alt="Digital ticket preview"
                        width="420"
                        height="320"
                        loading="lazy"
                        class="ely-showcase-img"
                    />
                </div>
            </div>
        </div>
    </section>

    <!-- Multi-Service Catalog -->
    <section class="ely-services" aria-labelledby="ely-services-heading">
        <div class="container">
            <div class="ely-section-header text-center">
                <h2 id="ely-services-heading">{{ __('More Services Coming Soon') }}</h2>
                <p>{{ __('EataLyft is expanding into a multi-service digital platform. Explore what\'s available and what\'s coming.') }}</p>
            </div>
            <div class="ely-services-grid">
                @php
                    $services = config('services.services');
                    usort($services, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));
                @endphp
                @foreach($services as $service)
                    <article class="ely-service-card ely-reveal @if($service['status'] === 'coming_soon') ely-service-coming-soon @endif">
                        <div class="ely-service-header">
                            @if(!empty($service['icon']))
                                <x-eatalyft.image
                                    manifestKey="{{ $service['icon'] }}"
                                    alt="{{ $service['name'] }} illustration"
                                    width="80"
                                    height="80"
                                    class="ely-service-icon"
                                />
                            @endif
                            @if($service['status'] !== 'active')
                                <span class="ely-service-badge {{ config('services.status_labels.' . $service['status'] . '.badge_class') }}">
                                    {{ config('services.status_labels.' . $service['status'] . '.text') }}
                                </span>
                            @endif
                        </div>
                        <div class="ely-service-body">
                            <h3>{{ $service['name'] }}</h3>
                            <p>{{ $service['short_description'] }}</p>
                            <ul class="ely-service-features">
                                @foreach($service['features'] as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ $service['cta_url'] }}" class="ely-btn ely-btn-{{ $service['cta_type'] }} ely-service-cta {{ $service['status'] === 'active' ? 'ely-btn-primary' : 'ely-btn-outline' }}">
                                {{ $service['cta_text'] }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Decorative bottom wave -->
    <div class="ely-deco-bottom">
        <img src="{{ asset('assets/eatalyft/decorations/bottom-wave.svg') }}" alt="" class="ely-deco-img" aria-hidden="true">
    </div>
@endsection

@push('scripts')
<script>
    // Search form - ensure service is set and prevent empty submission
    document.getElementById('ely-search-form')?.addEventListener('submit', function(e) {
        const from = document.getElementById('ely-from').value.trim();
        const to = document.getElementById('ely-to').value.trim();
        if (!from || !to) {
            e.preventDefault();
            return;
        }
    });
</script>
@endpush
