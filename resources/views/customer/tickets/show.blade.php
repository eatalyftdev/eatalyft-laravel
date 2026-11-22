@extends('layouts.app')

@section('title', __('Ticket') . ' - ' . $ticket->ticket_number)
@section('description', __('View your digital ticket ' . $ticket->ticket_number))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">{{ __('Digital Ticket') }}</h1>
            <a href="{{ route('dashboard.tickets') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i>{{ __('Back to My Tickets') }}
            </a>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Ticket Details') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Ticket Number') }}</strong></p>
                                <p class="text-monospace">{{ $ticket->ticket_number }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Passenger Name') }}</strong></p>
                                <p>{{ $ticket->passenger_name }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Seat') }}</strong></p>
                                <p>{{ $ticket->busTripSeat->seat_label ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Route') }}</strong></p>
                                <p>{{ $ticket->busTrip->route->originCity->name }} → {{ $ticket->busTrip->route->destinationCity->name }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Departure') }}</strong></p>
                                <p>{{ $ticket->busTrip->departure_at->format('l, M d, Y \\a\\t H:i') }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Arrival') }}</strong></p>
                                <p>{{ $ticket->busTrip->arrival_at->format('l, M d, Y \\a\\t H:i') }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Agency') }}</strong></p>
                                <p>{{ $ticket->busTrip->agency->name }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Fare') }}</strong></p>
                                <p>{{ $ticket->fare_minor }} {{ $ticket->currency }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Status') }}</strong></p>
                                <p>
                                    <span class="badge bg-{{ $ticket->status === 'issued' ? 'success' : ($ticket->status === 'used' ? 'primary' : 'danger') }}">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('QR Code') }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="ely-ticket-qr-code" id="ticketQrCode" data-ticket-number="{{ $ticket->ticket_number }}"></div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="bx bx-printer me-1"></i>{{ __('Print Ticket') }}
                            </button>
                        </div>
                        <div class="mt-3 text-muted small">
                            {{ __('Show this QR code at boarding for validation.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection