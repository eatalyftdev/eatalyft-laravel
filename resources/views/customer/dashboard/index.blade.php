@extends('layouts.app')

@section('title', __('Dashboard'))
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h1 class="h3 mb-4">{{ __('Dashboard') }}</h1>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Upcoming Trips') }}</h5>
                        <p class="card-text display-4 text-primary">{{ count($upcomingTrips) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Total Bookings') }}</h5>
                        <p class="card-text display-4 text-info">{{ $totalBookings }}</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Active Tickets') }}</h5>
                        <p class="card-text display-4 text-success">{{ $activeTickets }}</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Recent Bookings') }}</h5>
                        <p class="card-text display-4 text-warning">{{ count($recentBookings) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Trips -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('Upcoming Trips') }}</h5>
                <a href="{{ route('dashboard.bookings') }}" class="btn btn-sm btn-primary">{{ __('View All') }}</a>
            </div>
            <div class="card-body">
                @if($upcomingTrips->count() > 0)
                <div class="row">
                    @foreach($upcomingTrips as $trip)
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">{{ $trip->agency->name }}</h6>
                                <p class="card-text text-muted small">
                                    {{ $trip->busTrip->route->originCity->name }} → {{ $trip->busTrip->route->destinationCity->name }}
                                </p>
                                <p class="card-text">
                                    <strong>{{ $trip->busTrip->departure_at->format('M d, Y \\a\\t H:i') }}</span>
                                </p>
                                <p class="card-text text-success">
                                    <strong>{{ __('Seat') }}</span>: {{ $booking->busTripSeats->first()->seat_label ?? 'N/A' }}</span>
                                </p>
                                <a href="{{ route('bus.bookings.show', $trip->id) }}" class="btn btn-sm btn-primary">{{ __('View Details') }}</a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <p>{{ __('No upcoming trips found.') }}</p>
                    <a href="{{ route('search') }}?service=bus_tickets" class="btn btn-primary">{{ __('Search Trips') }}</a>
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('Recent Bookings') }}</h5>
                <a href="{{ route('dashboard.bookings') }}" class="btn btn-sm btn-primary">{{ __('View All') }}</a>
            </div>
            <div class="card-body">
                @if($recentBookings->count() > 0)
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Reference') }}</th>
                            <th>{{ __('Route') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentBookings as $booking)
                        <tr>
                            <td><strong>{{ $booking->booking_ref }}</strong></td>
                            <td>{{ $booking->busTrip->route->originCity->name }} → {{ $trip->route->destinationCity->name }}</td>
                            <td>{{ $booking->busTrip->departure_at->format('M d, Y \\a\\t H:i') }}</span>
                            <td>
                                <span class="badge bg-{{ $booking->status === 'confirmed' ? 'success' : ($booking->status === 'held' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($booking->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('bus.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center text-muted py-4">
                    <p>{{ __('No bookings found.') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection