@extends('layouts.public')

@section('title', $trip->agency->name . ' - ' . $trip->route->originCity->name . ' to ' . $trip->route->destinationCity->name . ' - ' . config('app.name'))
@section('description', __('Book your seat from {{ origin }} to {{ destination }} on {{ date }}.', ['origin' => $trip->route->originCity->name, 'destination' => $trip->route->destinationCity->name, 'date' => $trip->departure_at->format('M d, Y')]))

@section('content')
    <!-- Decorative top wave -->
    <div class="ely-deco-top">
        <img src="{{ asset('assets/eatalyft/decorations/top-wave.svg') }}" alt="" class="ely-deco-img" aria-hidden="true">
    </div>

    <!-- Trip Detail Header -->
    <section class="ely-trip-detail-header" aria-labelledby="ely-trip-detail-heading">
        <div class="container">
            <div class="ely-trip-detail-header-inner">
                <div class="ely-trip-detail-back">
                    <a href="{{ route('bus.search') }}?from_city_id={{ $trip->route->origin_city_id }}&to_city_id={{ $trip->route->destination_city_id }}&date={{ $trip->departure_at->format('Y-m-d') }}" class="ely-btn ely-btn-outline ely-btn-small">
                        <i class="bx bx-arrow-back me-1"></i> {{ __('Back to search results') }}
                    </a>
                </div>
                <div class="ely-trip-detail-info">
                    <h1 id="ely-trip-detail-heading">{{ $trip->agency->name }} - {{ $trip->route->originCity->name }} to {{ $trip->route->destinationCity->name }}</h1>
                    <p class="ely-trip-detail-subtitle">
                        <span class="ely-trip-date">{{ $trip->departure_at->format('l, M d, Y') }}</span>
                        <span class="ely-trip-separator">•</span>
                        <span class="ely-trip-time">{{ $trip->departure_at->format('H:i') }} - {{ $trip->arrival_at->format('H:i') }}</span>
                        <span class="ely-trip-separator">•</span>
                        <span class="ely-trip-duration">{{ $trip->departure_at->diffInMinutes($trip->arrival_at) }} min</span>
                    </p>
                    <div class="ely-trip-agency-badge">
                        <x-eatalyft.image manifestKey="placeholders.operator_logo" alt="{{ $trip->agency->name }}" width="32" height="32" objectFit="contain" />
                        <span>{{ $trip->agency->name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Route Stops -->
    <section class="ely-trip-route" aria-labelledby="ely-route-heading">
        <div class="container">
            <h2 id="ely-route-heading">{{ __('Route & Stops') }}</h2>
            <div class="ely-route-visual">
                @foreach($routeStops as $index => $stop)
                <div class="ely-route-stop {{ $index === 0 ? 'ely-route-stop-first' : '' }} {{ $index === $routeStops->count() - 1 ? 'ely-route-stop-last' : '' }}">
                    <div class="ely-route-stop-marker"></div>
                    <div class="ely-route-stop-content">
                        <span class="ely-trip-stop-name">{{ $stop->city->name }}</span>
                        <span class="ely-trip-stop-time">{{ $trip->departure_at->copy()->addMinutes($stop->offset_minutes)->format('H:i') }}</span>
                        @if($stop->branch)
                            <span class="ely-trip-stop-branch">{{ $stop->branch->name }}</span>
                        @endif
                    </div>
                    @if($index < $routeStops->count() - 1)
                    <div class="ely-route-connector"></div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Seat Map -->
    <section class="ely-seat-map" aria-labelledby="ely-seat-map-heading">
        <div class="container">
            <div class="ely-section-header">
                <h2 id="ely-seat-map-heading">{{ __('Select Your Seat') }}</h2>
                <p>{{ __('Choose your preferred seat for this trip.') }}</p>
            </div>

            <div class="ely-seat-map-container">
                <div class="ely-bus-layout">
                    <!-- Bus outline -->
                    <div class="ely-bus-outline">
                        <div class="ely-bus-driver">
                            <span class="ely-driver-icon">🚗</span>
                            <span class="ely-driver-label">{{ __('Driver') }}</span>
                        </div>
                        <div class="ely-bus-door">
                            <span class="ely-door-icon">🚪</span>
                            <span class="ely-door-label">{{ __('Door') }}</span>
                        </div>
                        <div class="ely-bus-seats">
                            @php
                                $seatLayout = $trip->bus->seat_layout ?? null;
                                $seatCells = $seatLayout ? $seatLayout->seat_layout_cells()->orderBy('row')->orderBy('col')->get() : collect();
                                $tripSeats = $trip->seats->keyBy('seat_label');
                            @endphp

                            @foreach($seatCells as $cell)
                            @php
                                $tripSeat = $tripSeats->firstWhere('seat_label', $cell->label ?? ($cell->row . chr(65 + $cell->col)));
                                $isAvailable = !$tripSeat || $tripSeat->status === 'available';
                                $isHeld = $tripSeat && $tripSeat->status === 'held';
                                $isSold = $tripSeat && $tripSeat->status === 'sold';
                                $seatClass = 'ely-seat';
                                if ($isSold) $seatClass .= ' ely-seat-sold';
                                elseif ($isHeld) $seatClass .= ' ely-seat-held';
                                else $seatClass .= ' ely-seat-available';
                            @endphp
                            <button class="{{ $seatClass }}"
                                @if($isAvailable) data-seat="{{ $cell->label }}" data-price="{{ $tripSeat->price_minor ?? 0 }}" @endif
                                {{ $isSold || $isHeld ? 'disabled' : '' }}
                                title="{{ $cell->label }} - {{ $isSold ? __('Sold') : ($isHeld ? __('Held') : __('Available')) }} - {{ $tripSeat->price_minor ?? 0 }} {{ $tripSeat->currency ?? 'XAF' }}">
                                {{ $cell->label }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="ely-seat-summary">
                    <div class="ely-seat-summary-card">
                        <div class="ely-seat-summary-header">
                            <h3>{{ __('Your Selection') }}</h3>
                            <a href="#" class="ely-seat-summary-clear">{{ __('Clear all') }}</a>
                        </div>
                        <div class="ely-seat-summary-items">
                            <!-- Selected seats will appear here via JavaScript -->
                            <div class="ely-seat-summary-empty">{{ __('No seats selected') }}</div>
                        </div>
                        <div class="ely-seat-summary-footer">
                            <div class="ely-seat-summary-total">
                                <span>{{ __('Total') }}</span>
                                <span class="ely-seat-summary-total-amount" id="ely-total-amount">0 <span class="ely-seat-summary-total-currency">XAF</span></span>
                            </div>
                            <button class="ely-btn ely-btn-primary ely-btn-large" id="ely-proceed-btn" disabled>{{ __('Continue to Passenger Details') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Decorative bottom wave -->
    <div class="ely-deco-bottom">
        <img src="{{ asset('assets/eatalyft/decorations/bottom-wave.svg') }}" alt="" class="ely-deco-img" aria-hidden="true">
    </div>
@endsection

@section('scripts')
<script>
    // Seat selection logic
    document.addEventListener('DOMContentLoaded', function() {
        const seats = document.querySelectorAll('.ely-seat-available');
        const selectedSeats = new Set();
        const totalAmountEl = document.getElementById('ely-total-amount');
        const proceedBtn = document.getElementById('ely-proceed-btn');
        const summaryList = document.querySelector('.ely-seat-summary-items');
        const emptyState = document.querySelector('.ely-seat-summary-empty');

        let totalAmount = 0;

        seats.forEach(seat => {
            seat.addEventListener('click', function() {
                const seatLabel = this.getAttribute('data-seat');
                const seatPrice = parseInt(this.getAttribute('data-price'));

                if (selectedSeats.has(seatLabel)) {
                    // Deselect
                    selectedSeats.delete(seatLabel);
                    this.classList.remove('ely-seat-selected');
                    totalAmount -= seatPrice;
                } else {
                    // Select
                    selectedSeats.add(seatLabel);
                    this.classList.add('ely-seat-selected');
                    totalAmount += seatPrice;
                }

                updateSummary();
            });
        });

        function updateSummary() {
            if (selectedSeats.size === 0) {
                emptyState.style.display = 'block';
                summaryList.innerHTML = '';
                proceedBtn.disabled = true;
                totalAmountEl.textContent = '0';
            } else {
                emptyState.style.display = 'none';
                proceedBtn.disabled = false;
                totalAmountEl.textContent = totalAmount;

                summaryList.innerHTML = '';
                selectedSeats.forEach(seatLabel => {
                    const seat = document.querySelector(`[data-seat="${seatLabel}"]`);
                    const price = seat.getAttribute('data-price');
                    const item = document.createElement('div');
                    item.className = 'ely-seat-summary-item';
                    item.innerHTML = `
                        <span class="ely-seat-summary-item-label">${seatLabel}</span>
                        <span class="ely-seat-summary-item-price">${price} XAF</span>
                        <button type="button" class="ely-seat-summary-item-remove" data-seat="${seatLabel}">&times;</button>
                    `;
                    summaryList.appendChild(item);
                });

                // Add remove button listeners
                document.querySelectorAll('.ely-seat-summary-item-remove').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const seatLabel = this.getAttribute('data-seat');
                        const seat = document.querySelector(`[data-seat="${seatLabel}"]`);
                        if (seat) {
                            seat.classList.remove('ely-seat-selected');
                            selectedSeats.delete(seatLabel);
                            totalAmount -= parseInt(seat.getAttribute('data-price'));
                            updateSummary();
                        }
                    });
                });
            }
        }

        // Proceed to passenger details
        proceedBtn.addEventListener('click', function() {
            const seatLabels = Array.from(selectedSeats);
            const url = new URL(window.location.origin + '/bus/seats/select');
            url.searchParams.append('trip_id', {{ $trip->id }});
            seatLabels.forEach(label => url.searchParams.append('seats[]', label));
            window.location.href = url.toString();
        });
    });
</script>
@endsection