@extends('layouts.app')

@section('title', __('My Tickets'))
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h1 class="h3 mb-4">{{ __('My Tickets') }}</h1>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('dashboard.tickets') }}" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>{{ __('Issued') }}</option>
                            <option value="used" {{ request('status') === 'used' ? 'selected' : '' }}>{{ __('Used') }}</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</span>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>{{ __('Expired') }}</span>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Search') }}</label>
                        <input type="text" name="search" class="form-control" placeholder="{{ __('Search by ticket number...') }}" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">{{ __('Filter') }}</button>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('dashboard.tickets') }}" class="btn btn-outline-secondary w-100">{{ __('Reset') }}</a>
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
                                <th>{{ __('Ticket Number') }}</th>
                                <th>{{ __('Route') }}</th>
                                <th>{{ __('Seat') }}</th>
                                <th>{{ __('Departure') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($tickets->count() > 0)
                            @foreach($tickets as $ticket)
                            <tr>
                                <td><strong>{{ $ticket->ticket_number }}</strong></td>
                                <td>{{ $ticket->busTrip->route->originCity->name }} → {{ $ticket->busTrip->route->destinationCity->name }}</td>
                                <td>{{ $ticket->busTripSeat->seat_label ?? 'N/A' }}</td>
                                <td>{{ $ticket->busTrip->departure_at->format('M d, Y \\a\\t H:i') }}</span>
                                <td>
                                    <span class="badge bg-{{ $ticket->status === 'issued' ? 'success' : ($ticket->status === 'used' ? 'primary' : 'danger') }}">
                                        {{ ucfirst($ticket->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('bus.tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                </td>
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('No tickets found.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $tickets->links() }}
            </div>
        </div>
    </div>
@endsection