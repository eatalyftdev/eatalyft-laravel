@extends('layouts.app')

@section('title', __('Bus Bookings'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Bus Bookings') }}</h1>
    @can('bus.bookings.write')
        <a href="{{ route('bus.bookings.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> {{ __('Add Booking') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Passenger') }}</th>
                        <th>{{ __('Trip') }}</th>
                        <th>{{ __('Seats') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">{{ __('No bookings found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection