@extends('layouts.app')

@section('title', __('Trip') . ' - ' . $trip->busTrip->route->originCity->name . ' to ' . $trip->busTrip->route->destinationCity->name)
@section('description', __('Manage trip ' . $trip->busTrip->id))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h1 class="h3 mb-4">{{ __('Trip Details') }}</h1>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Route') }}</strong></p>
                                <p>{{ $trip->busTrip->route->originCity->name }} → {{ $trip->busTrip->route->destinationCity->name }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Departure') }}</strong></p>
                                <p>{{ $trip->busTrip->departure_at->format('l, M d, Y \\a\\t H:i') }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Arrival') }}</strong></p>
                                <p>{{ $trip->busTrip->arrival_at->format('l, M d, Y \\a\\t H:i') }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Bus') }}</strong></p>
                                <p>{{ $trip->busTrip->bus->plate ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Status') }}</strong></p>
                                <p>
                                    <span class="badge bg-{{ $trip->busTrip->status === 'scheduled' ? 'primary' : ($trip->busTrip->status === 'boarding' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($trip->busTrip->status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Capacity') }}</strong></p>
                                <p>{{ $trip->busTrip->capacity }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Seat Inventory') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Seat') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Booking') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trip->busTripSeats as $seat)
                                    <tr>
                                        <td>{{ $seat->seat_label }}</span>
                                        <td>{{ ucfirst($seat->seat_type) }}</span>
                                        <td>
                                            <span class="badge bg-{{ $seat->status === 'available' ? 'success' : ($seat->status === 'held' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($seat->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $seat->price_minor }} {{ $seat->currency }}</span>
                                        <td>{{ $seat->booking_id ? '#' . $seat->booking_id->booking_ref : '-' }}</span>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Ticket Validation') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('agency.bus.scan', $trip->id) }}">
                            @csrf
                            <div class="input-group mb-3">
                                <input type="text" name="ticket_number" class="form-control" placeholder="{{ __('Scan ticket number') }}" required>
                                <button class="btn btn-primary" type="submit">{{ __('Scan') }}</button>
                            </div>
                        </div>
                        @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                        @endif
                        @if($errors->has('ticket_number'))
                        <div class="alert alert-danger">{{ $errors->first('ticket_number') }}</div>
                        @endif
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Trip Actions') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary w-100" disabled>
                                <i class="bx bx-refresh me-2"></i>{{ __('Mark Departed') }}
                            </button>
                            <button class="btn btn-outline-secondary w-100">
                                <i class="bx bx-check me-2"></i>{{ __('Mark Completed') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection