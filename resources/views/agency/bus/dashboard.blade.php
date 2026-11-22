@extends('layouts.app')

@section('title', __('Agency Dashboard'))
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h1 class="h3 mb-4">{{ __('Agency Dashboard') }} - {{ $agency->name }}</h1>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Total Trips') }}</h5>
                        <p class="card-text display-4 text-primary">{{ $stats['total_trips'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Active Trips') }}</h5>
                        <p class="card-text display-4 text-info">{{ $stats['active_trips'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Total Bookings') }}</h5>
                        <p class="card-text display-4 text-success">{{ $stats['total_bookings'] }}</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Seat Inventory') }}</h5>
                        <p class="card-text display-4 text-warning">{{ $stats['held_seats'] }} Held / {{ $stats['sold_seats'] }} Sold</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Trips -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('Upcoming Trips') }}</h5>
                <a href="{{ route('admin.agencies.{agency}.trips') ?? '#' }}" class="btn btn-sm btn-primary">{{ __('Manage All Trips') }}</a>
            </div>
            <div class="card-body">
                @if($trips->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Route') }}</th>
                                <th>{{ __('Departure') }}</th>
                                <th>{{ __('Arrival') }}</th>
                                <th>{{ __('Bus') }}</th>
                                <th>{{ __('Seats') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trips as $trip)
                            <tr>
                                <td>
                                    <strong>{{ $trip->busRoute->originCity->name }}</span> → {{ $trip->busTrip->route->destinationCity->name }}</span>
                                </td>
                                <td>{{ $trip->busTrip->departure_at->format('M d, Y \\a\\t H:i') }}</span>
                                <td>{{ $trip->busTrip->arrival_at->format('M d, Y \\a\\t H:i') }}</span>
                                <td>{{ $booking->busTrip->bus->plate ?? 'N/A' }}</span>
                                <td>
                                    <span class="badge bg-{{ $trip->busTripSeats->where('status', 'available')->count() > 0 ? 'success' : 'warning' }}">
                                        {{ $trip->busTripSeats->where('status', 'available')->count() }} / {{ $trip->busTripSeats->count() }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $trip->status === 'scheduled' ? 'primary' : ($trip->status === 'boarding' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($trip->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('agency.bus.trips.show', $trip->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <p>{{ __('No upcoming trips found.') }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection