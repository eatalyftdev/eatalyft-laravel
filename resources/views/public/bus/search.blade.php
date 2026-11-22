@extends('layouts.public')

@section('title', __('Bus Trips') . ' - ' . config('app.name'))
@section('description', __('Find and book bus tickets across West Africa with EataLyft.'))

@section('content')
    <!-- Decorative top wave -->
    <div class="ely-deco-top">
        <img src="{{ asset('assets/eatalyft/decorations/top-wave.svg') }}" alt="" class="ely-deco-img" aria-hidden="true">
    </div>

    <!-- Search Section -->
    <section class="ely-search" aria-labelledby="ely-search-heading">
        <div class="container">
            <div class="ely-search-card">
                <form action="{{ route('search') }}" method="GET" class="ely-search-form" id="ely-search-form">
                    <input type="hidden" name="service" value="bus_tickets">
                    <div class="ely-search-row">
                        <div class="ely-search-field">
                            <label for="from_city_id">{{ __('From') }}</label>
                            <x-eatalyft.image manifestKey="icons.location-marker" alt="" width="20" height="20" class="ely-search-icon" />
                            <select name="from_city_id" id="from_city_id" class="ely-search-select" {{ $validated['from_city_id'] ?? old('from_city_id') ? '' : 'autofocus' }}>
                                <option value="">{{ __('Select departure city') }}</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ (request('from_city_id') ?? old('from_city_id')) == $city->id ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ely-search-field">
                            <label for="to_city_id">{{ __('To') }}</label>
                            <x-eatalyft.image manifestKey="icons.location-marker" alt="" width="20" height="20" class="ely-search-icon" />
                            <select name="to_city_id" id="to_city_id" class="ely-search-select">
                                <option value="">{{ __('Select destination') }}</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ (request('to_city_id') ?? old('to_city_id')) == $city->id ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ely-search-field">
                            <label for="date">{{ __('Date') }}</label>
                            <x-eatalyft.image manifestKey="icons.clock" alt="" width="20" height="20" class="ely-search-icon" />
                            <input type="date" name="date" id="date" value="{{ request('date') ?? old('date') ?? now()->format('Y-m-d') }}" min="{{ now()->format('Y-m-d') }}" aria-label="{{ __('Travel date') }}" required>
                        </div>
                        <div class="ely-search-field ely-search-field-passengers">
                            <label for="passengers">{{ __('Passengers') }}</label>
                            <x-eatalyft.image manifestKey="icons.seat" alt="" width="20" height="20" class="ely-search-icon" />
                            <select name="passengers" id="passengers" class="ely-search-select">
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ (request('passengers') ?? old('passengers')) == $i ? 'selected' : '' }}>{{ $i }} {{ $i === 1 ? __('passenger') : __('passengers') }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit" class="ely-btn ely-btn-primary ely-btn-large ely-search-submit">{{ __('Search trips') }}</button>
                    </div>
                    <p class="ely-search-note">{{ __('Search results show available routes and seats. Seat holds are temporary reservations until booking is completed.') }}</p>
                </form>
            </div>
        </div>
    </section>

    <!-- Search Results -->
    @if($trips->count() > 0)
    <section class="ely-trip-results" aria-labelledby="ely-results-heading">
        <div class="container">
            <div class="ely-section-header">
                <h2 id="ely-results-heading">{{ __('Available Trips') }}</h2>
                <p>{{ __('Found {{ count }} trips for your search.', ['count' => $trips->total()]) }}</p>
            </div>

            <div class="ely-trip-list">
                @foreach($trips as $trip)
                <div class="ely-trip-card">
                    <div class="ely-trip-header">
                        <div class="ely-trip-agency">
                            @if($trip->agency->logo_url)
                                <x-eatalyft.image src="{{ $trip->agency->logo_url }}" manifestKey="placeholders.operator_logo" alt="{{ $trip->agency->name }} logo" width="40" height="40" class="ely-agency-logo" objectFit="contain" />
                            @else
                                <x-eatalyft.image manifestKey="placeholders.operator_logo" alt="{{ $trip->agency->name }} logo" width="40" height="40" class="ely-agency-logo" objectFit="contain" />
                            @endif
                            <div class="ely-trip-agency-info">
                                <span class="ely-trip-agency-name">{{ $trip->agency->name }}</span>
                                <span class="ely-trip-agency-rating">{{ __('Verified Agency') }}</span>
                            </div>
                        </div>
                        <div class="ely-trip-timing">
                            <div class="ely-trip-time-departure">
                                <span class="ely-trip-time">{{ $trip->departure_at->format('H:i') }}</span>
                                <span class="ely-trip-date">{{ $trip->departure_at->format('M d, Y') }}</span>
                            </div>
                            <div class="ely-trip-duration">
                                <span class="ely-trip-duration-text">{{ $trip->departure_at->diffInMinutes($trip->arrival_at) }} min</span>
                                <span class="ely-trip-duration-label">{{ __('duration') }}</span>
                            </div>
                            <div class="ely-trip-time-arrival">
                                <span class="ely-trip-time">{{ $trip->arrival_at->format('H:i') }}</span>
                                <span class="ely-trip-date">{{ $trip->arrival_at->format('M d') }}</span>
                            </div>
                        </div>
                        <div class="ely-trip-price">
                            <span class="ely-trip-price-amount">{{ number_format($trip->seats->first()->price_minor ?? 0 / 100, 0) }} <span class="ely-trip-price-currency">{{ $trip->seats->first()->currency ?? 'XAF' }}</span></span>
                            <span class="ely-trip-price-per-seat">{{ __('per seat') }}</span>
                        </div>
                        <a href="{{ route('bus.trip.show', $trip) }}" class="ely-btn ely-btn-primary ely-btn-small">{{ __('Select Seats') }}</a>
                    </div>

                    <div class="ely-trip-details">
                        <div class="ely-trip-route">
                            <span class="ely-trip-route-origin">{{ $trip->route->originCity->name }}</span>
                            <span class="ely-trip-route-arrow">→</span>
                            <span class="ely-trip-route-destination">{{ $trip->route->destinationCity->name }}</span>
                        </div>

                        <div class="ely-trip-amenities">
                            @foreach($trip->seats->first()?->bus?->amenities ?? [] as $amenity)
                                <span class="ely-trip-amenity-badge">{{ $amenity->name }}</span>
                            @endforeach
                        </div>

                        <div class="ely-trip-seats-available">
                            <span class="ely-trip-seats-count">{{ $trip->seats->where('status', 'available')->count() }}</span>
                            <span class="ely-trip-seats-label">{{ __('seats available') }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{ $trips->links() }}
        </div>
    </section>
    @else
    <section class="ely-empty-state" aria-labelledby="ely-empty-heading">
        <div class="container">
            <div class="ely-empty-card">
                <div class="ely-empty-icon">
                    <x-eatalyft.image manifestKey="icons.search" alt="{{ __('No trips found') }}" width="64" height="64" />
                </div>
                <h2 id="ely-empty-heading">{{ __('No trips found') }}</h2>
                <p>{{ __('We couldn\'t find any trips matching your search criteria. Try adjusting your search parameters.') }}</p>
                <a href="{{ route('search') }}?service=bus_tickets" class="ely-btn ely-btn-primary">{{ __('Modify Search') }}</a>
            </div>
        </div>
    </section>
    @endif

    <!-- Decorative bottom wave -->
    <div class="ely-deco-bottom">
        <img src="{{ asset('assets/eatalyft/decorations/bottom-wave.svg') }}" alt="" class="ely-deco-img" aria-hidden="true">
    </div>
@endsection