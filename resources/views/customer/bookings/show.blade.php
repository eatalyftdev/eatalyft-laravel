@extends('layouts.app')

@section('title', __('Booking') . ' - ' . $booking->booking_ref)
@section('description', __('View your booking details for ' . $booking->booking_ref))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">{{ __('Booking') }} #{{ $booking->booking_ref }}</h1>
            <a href="{{ route('dashboard.bookings') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>{{ __('Back to My Bookings') }}
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Trip Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Route') }}</strong></p>
                                <p>{{ $booking->busTrip->route->originCity->name }} → {{ $booking->busTrip->route->destinationCity->name }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Departure') }}</strong></p>
                                <p>{{ $booking->busTrip->departure_at->format('l, M d, Y \\a\\t H:i') }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Arrival') }}</strong></p>
                                <p>{{ $booking->busTrip->arrival_at->format('l, M d, Y \\a\\t H:i') }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Agency') }}</strong></p>
                                <p>{{ $booking->agency->name }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Seat') }}</strong></p>
                                <p>{{ $booking->busTripSeats->first()->seat_label ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Total Price') }}</strong></p>
                                <p>{{ $booking->total_minor }} {{ $booking->currency }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Booking Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Booking Reference') }}</strong></p>
                                <p class="text-monospace">{{ $booking->booking_ref }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Contact Name') }}</strong></p>
                                <p>{{ $booking->contact_name }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Contact Phone') }}</strong></p>
                                <p>{{ $booking->contact_phone }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Contact Email') }}</strong></p>
                                <p>{{ $booking->contact_email ?? __('Not provided') }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Status') }}</strong></p>
                                <p>
                                    <span class="badge bg-{{ $booking->status === 'confirmed' ? 'success' : ($booking->status === 'held' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Created At') }}</strong></p>
                                <p>{{ $booking->created_at->format('M d, Y \\a\\t H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($booking->tickets->count() > 0)
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Your Ticket') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($booking->tickets as $ticket)
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">{{ $ticket->ticket_number }}</span>
                                        <p class="mb-1">{{ __('Seat') }}: {{ $ticket->busTripSeat->seat_label ?? 'N/A' }}</span>
                                        <p class="mb-0">{{ __('Status') }}: <span class="badge bg-{{ $ticket->status === 'issued' ? 'success' : 'secondary' }}">{{ ucfirst($ticket->status) }}</span></span>
                                    </div>
                                    <a href="{{ route('bus.tickets.show', $ticket->id) }}" class="btn btn-sm btn-primary w-100">{{ __('View Ticket') }}</a>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Actions') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($booking->status === 'held')
                        <a href="{{ route('bus.bookings.payment', $booking->id) }}" class="btn btn-primary w-100 mb-2">
                            <i class="bx bx-credit-card me-2"></i>{{ __('Proceed to Payment') }}
                        </a>
                        @endif
                        <a href="{{ route('bus.bookings.show', $booking->id) }}" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="bx bx-info-circle me-2"></i>{{ __('View Details') }}
                        </a>
                        <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelBookingModal">
                            <i class="bx bx-trash me-2"></i>{{ __('Cancel Booking') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Modal -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Cancel Booking') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Are you sure you want to cancel this booking? Your seat hold will be released.') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <a href="{{ route('bus.bookings.cancel', $booking->id) }}" class="btn btn-danger">{{ __('Cancel Booking') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection