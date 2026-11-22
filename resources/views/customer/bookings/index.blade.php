@extends('layouts.app')

@section('title', __('My Bookings'))
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h1 class="h3 mb-4">{{ __('My Bookings') }}</h1>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('dashboard.bookings') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="held" {{ request('status') === 'held' ? 'selected' : '' }}>{{ __('Held') }}</option>
                            <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>{{ __('Confirmed') }}</span>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</span>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</span>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="search" class="form-control" placeholder="{{ __('Search by reference...') }}" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('dashboard.bookings') }}" class="btn btn-outline-secondary w-100">{{ __('Reset') }}</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Route') }}</th>
                                <th>{{ __('Departure') }}</th>
                                <th>{{ __('Seat') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($bookings->count() > 0)
                            @foreach($bookings as $booking)
                            <tr>
                                <td><strong>{{ $booking->booking_ref }}</strong></td>
                                <td>{{ $booking->busTrip->route->originCity->name }} → {{ $trip->route->destinationCity->name }}</td>
                                <td>{{ $booking->busTrip->departure_at->format('M d, Y \\a\\t H:i') }}</span>
                                <td>{{ $booking->busTripSeats->first()->seat_label ?? 'N/A' }}</span>
                                <td>{{ $booking->total_minor }} {{ $booking->currency }}</span>
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
                            @else
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('No bookings found.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $bookings->links() }}
            </div>
        </div>
    </div>
@endsection