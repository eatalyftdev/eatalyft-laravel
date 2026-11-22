@extends('layouts.public')

@section('title', __('Select Seats') . ' - ' . $trip->agency->name . ' - ' . config('app.name'))
@section('description', __('Select your seat for the trip from ' . $trip->route->originCity->name . ' to ' . $trip->route->destinationCity->name))

@section('content')
    <!-- Decorative top wave -->
    <div class="ely-deco-top">
        <img src="{{ asset('assets/eatalyft/decorations/top-wave.svg') }}" alt="" class="ely-deco-img" aria-hidden="true">
    </div>

    <!-- Trip Header -->
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
                                $seatLabels = $seatLabels ?? [];
                                $tripSeats = $seats->keyBy('seat_label');
                            @endphp
                            @foreach($seatCells as $cell)
                            @php
                                $tripSeat = $tripSeats->firstWhere('seat_label', $cell->label ?? ($cell->row . chr(65 + $cell->col)));
                                $isAvailable = !$tripSeat || $tripSeat->status === 'available';
                                $isHeld = $tripSeat && $tripSeat->status === 'held';
                                $isSold = $tripSeat && $tripSeat->status === 'sold';
                                $isSelected = in_array($cell->label ?? ($cell->row . chr(65 + $cell->col)), $seatLabels);
                                $seatClass = 'ely-seat';
                                if ($isSold) $seatClass .= ' ely-seat-sold';
                                elseif ($isHeld) $seatClass .= ' ely-seat-held';
                                elseif ($isSelected) $seatClass .= ' ely-seat-selected';
                                else $seatClass .= ' ely-seat-available';
                            @endphp
                            @if($isAvailable || $isSelected)
                            <button class="{{ $seatClass }}"
                                data-seat="{{ $cell->label }}"
                                data-price="{{ $tripSeat->price_minor ?? 0 }}"
                                title="{{ $cell->label }} - {{ $isSold ? __('Sold') : ($isHeld ? __('Held') : __('Available')) }} - {{ $tripSeat->price_minor ?? 0 }} {{ $tripSeat->currency ?? 'XAF' }}">
                                {{ $cell->label }}
                                </button>
                                @endif
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
                            @if(count($seatLabels) === 0)
                            <div class="ely-seat-summary-empty">{{ __('No seats selected') }}</div>
                            @else
                                @foreach($seatLabels as $seatLabel)
                                @php
                                    $tripSeat = $tripSeats->firstWhere('seat_label', $seatLabel);
                                @endphp
                                <div class="ely-seat-summary-item">
                                    <span class="ely-seat-summary-item-label">{{ $seatLabel }}</span>
                                    <span class="ely-seat-summary-item-price">{{ $tripSeat->price_minor ?? 0 }} {{ $tripSeat->currency ?? 'XAF' }}</span>
                                    <button type="button" class="ely-seat-summary-item-remove" data-seat="{{ $seatLabel }}">&times;</button>
                                </div>
                                @endforeach
                                @endif
                        </div>
                        <div class="ely-seat-summary-footer">
                            <div class="ely-seat-summary-total">
                                <span>{{ __('Total') }}</span>
                                <span class="ely-seat-summary-total-amount" id="ely-total-amount">
                                    @php
                                        $total = 0;
                                        foreach($seatLabels as $label) {
                                            $ts = $tripSeats->firstWhere('seat_label', $label);
                                            if($ts) $total += $ts->price_minor ?? 0;
                                        }
                                        echo $total;
                                    @endphp
                                    <span class="ely-seat-summary-total-currency">{{ $seats->first()->currency ?? 'XAF' ?? 'XAF' }}</span>
                                </div>
                                <form id="ely-seat-selection-form" action="{{ route('bus.bookings.create') }}" method="POST" style="display: none;">
                                    @csrf
                                    <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                                    @foreach($seatLabels as $seatLabel)
                                    <input type="hidden" name="seat_labels[]" value="{{ $seatLabel }}">
                                    @endforeach
                                    <input type="hidden" name="contact_name" value="{{ old('contact_name', auth()->user()->name ?? '') }}">
                                    <input type="hidden" name="contact_phone" value="{{ old('contact_phone', auth()->user()->phone ?? '') }}">
                                    <input type="hidden" name="contact_email" value="{{ old('contact_email', auth()->user()->email ?? '') }}">
                                </form>
                                <button class="ely-btn ely-btn-primary ely-btn-large" id="ely-proceed-btn" {{ count($seatLabels) === 0 ? 'disabled' : '' }}>
                                    {{ __('Continue to Passenger Details') }}
                                </button>
                            </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const seats = document.querySelectorAll('.ely-seat-available');
        const proceedBtn = document.getElementById('ely-proceed-btn');
        const totalAmountEl = document.getElementById('ely-total-amount');
        const summaryList = document.querySelector('.ely-seat-summary-items');
        const emptyState = document.querySelector('.ely-seat-summary-empty');

        let selectedSeats = new Set();

        // Initialize with pre-selected seats
        @foreach($seatLabels as $seatLabel)
        selectedSeats.add('{{ $seatLabel }}');
        document.querySelector('[data-seat="{{ $seatLabel }}"]')?.classList.add('ely-seat-selected');
        @endforeach

        let totalAmount = parseInt(document.getElementById('ely-total-amount').textContent.replace(/\D/g,'') || 0);

        seats.forEach(seat => {
            seat.addEventListener('click', function() {
                const seatLabel = this.getAttribute('data-seat');
                const seatPrice = parseInt(this.getAttribute('data-price'));

                if (selectedSeats.has(seatLabel)) {
                    selectedSeats.delete(seatLabel);
                    this.classList.remove('ely-seat-selected');
                    totalAmount -= seatPrice;
                } else {
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

        document.getElementById('ely-seat-selection-form').addEventListener('submit', function(e) {
            const seatInputs = document.querySelectorAll('input[name="seat_labels[]"]');
            seatInputs.forEach(input => input.value = '');
            selectedSeats.forEach((seatLabel, index) => {
                seatInputs[index].value = seatLabel;
            });
        });
    });
</script>