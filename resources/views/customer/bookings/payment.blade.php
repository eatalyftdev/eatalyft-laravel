@extends('layouts.app')

@section('title', __('Payment') . ' - ' . $booking->booking_ref)
@section('description', __('Complete your payment for booking ' . $booking->booking_ref))

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h1 class="h3 mb-4">{{ __('Complete Payment') }}</h1>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Booking Summary') }}</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Booking Ref') }}</strong></p>
                                <p>{{ $booking->booking_ref }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Route') }}</strong></p>
                                <p>{{ $booking->busTrip->route->originCity->name }} → {{ $trip->route->destinationCity->name }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Departure') }}</strong></p>
                                <p>{{ $booking->busTrip->departure_at->format('l, M d, Y \\a\\t H:i') }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Seat') }}</strong></p>
                                <p>{{ $booking->busTripSeats->first()->seat_label ?? 'N/A' }}</span>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Total') }}</strong></p>
                                <p>{{ $booking->total_minor }} {{ $booking->currency }}</span>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Payment Method') }}</strong></p>
                                <div id="paymentMethodDisplay">
                                    <i class="bx bx-credit-card me-2"></i>{{ __('Credit/Debit Card') }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>{{ __('Payment Status') }}</strong></p>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Payment Form') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('bus.bookings.payment', $booking->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('Payment Method') }}</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="card">{{ __('Credit/Debit Card') }}</option>
                                    <option value="mobile_money">{{ __('Mobile Money') }}</option>
                                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                </select>
                                <div class="form-text">{{ __('Payment processing is not yet available. This is a placeholder interface.') }}</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" {{ !$paymentAvailable || !$canConfirm['allowed'] ? 'disabled' : '' }}>
                                    @if(!$paymentAvailable)
                                    {{ __('Coming Soon') }}
                                    @elseif(!$canConfirm['allowed'])
                                    {{ __('Coming Soon') }}
                                    @else
                                    {{ __('Pay Now') }}
                                    @endif
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Important Notes') }}</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="bx bx-info-circle me-2 text-info"></i>
                                {{ __('Your seat hold will expire in 10 minutes. Please complete payment before then.') }}
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-info-circle me-2 text-info"></i>
                                {{ __('Payment processing is not yet available. Your booking will remain in held status until you complete payment.') }}
                            </li>
                            <li class="mb-2">
                                <i class="bx bx-info-circle me-2 text-info"></i>
                                {{ __('No payment will be processed until the payment phase is activated.') }}
                            </li>
                        </ul>
                        <div class="alert alert-info">
                            <i class="bx bx-shield-check me-2"></i>
                            <strong>{{ __('Secure Payment') }}</span>
                            <span class="d-block mt-1">{{ __('All payments are processed securely through our payment partners.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection