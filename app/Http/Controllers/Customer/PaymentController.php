<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Bus\Models\BusBooking;
use App\Services\Notification\BookingNotificationService;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected BookingNotificationService $notificationService;

    public function __construct(PaymentService $paymentService, BookingNotificationService $notificationService)
    {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
    }

    /**
     * Show payment page for a booking.
     */
    public function show(BusBooking $booking)
    {
        // Authorization: customer can only view their own bookings
        if ($booking->customer_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this booking.');
        }

        if ($booking->status !== 'held') {
            abort(409, 'Booking is not in a payable state.');
        }

        $paymentAvailable = $this->paymentService->isAvailable();
        $canConfirm = $this->paymentService->canConfirmPayment($booking);

        return view('customer.bookings.payment', compact('booking', 'paymentAvailable', 'canConfirm'));
    }

    /**
     * Process payment for a booking.
     */
    public function pay(Request $request, BusBooking $booking)
    {
        // Authorization: customer can only pay for their own bookings
        if ($booking->customer_id !== auth()->id()) {
            abort(403, 'You do not have permission to pay for this booking.');
        }

        if ($booking->status !== 'held') {
            return back()->withErrors(['booking' => 'Booking is not in a payable state.']);
        }

        $canConfirm = $this->paymentService->canConfirmPayment($booking);

        if (!$canConfirm['allowed']) {
            return back()->withErrors(['payment' => $canConfirm['reason']]);
        }

        $request->validate([
            'payment_method' => ['required', 'string', 'in:card,mobile_money,bank_transfer'],
        ]);

        try {
            $result = $this->paymentService->createPaymentIntent($booking, [
                'payment_method' => $request->payment_method,
            ]);

            if ($result['status'] !== 'confirmed') {
                return back()->withErrors([
                    'payment' => 'Payment processing is not available. Your seat hold remains active until it expires.',
                ]);
            }

            // Payment confirmed - issue ticket and update booking
            $ticket = $this->issueTicket($booking);

            // Notify customer of ticket issuance
            $this->notificationService->ticketIssued($ticket);

            return redirect()->route('bus.bookings.show', $booking)
        } catch (\Throwable $e) {
            \Log::error('Payment processing failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['payment' => 'Payment processing failed. Please try again.']);
        }
    }

    /**
     * Issue a digital ticket for a confirmed booking.
     *
     * @param  BusBooking  $booking
     * @return BusTicket
     */
    protected function issueTicket(BusBooking $booking): BusTicket
    {
        // Find the first seat assigned to this booking
        $seat = $booking->busTripSeats()->first();

        if (!$seat) {
            throw new \Exception('No seats found for this booking.');
        }

        // Generate ticket number
        $ticketNumber = 'ELB-' . strtoupper(\Illuminate\Support\Str::random(12));

        // Generate QR token hash (SHA-256 hash of ticket data, not raw ticket data)
        $qrTokenHash = hash('sha256', $ticketNumber . '|' . $booking->id . '|' . now()->timestamp);

        // Create ticket
        $ticket = BusTicket::create([
            'booking_id' => $booking->id,
            'bus_trip_id' => $booking->bus_trip_id,
            'bus_trip_seat_id' => $seat->id,
            'from_stop_id' => null, // Will be populated from trip route stops
            'to_stop_id' => null,   // Will be populated from trip route stops
            'passenger_name' => $booking->contact_name,
            'passenger_phone' => $booking->contact_phone,
            'fare_minor' => $booking->total_minor,
            'currency' => $booking->currency,
            'channel' => $booking->channel,
            'branch_id' => $booking->branch_id,
            'sold_by_user_id' => $booking->sold_by_user_id,
            'scanned_by' => null,
            'status' => 'issued',
            'qr_token_hash' => $qrTokenHash,
        ]);

        // Create ticket event
        \App\Modules\Bus\Models\BusTicketEvent::create([
            'bus_ticket_id' => $ticket->id,
            'actor_user_id' => auth()->id(),
            'event_type' => 'issued',
            'payload' => [
                'ticket_number' => $ticket->ticket_number,
                'issued_at' => now()->toISOString(),
                'issued_by' => auth()->id(),
            ],
            'source' => 'web',
        ]);

        // Update seat to link ticket
        $seat->update([
            'ticket_id' => $ticket->id,
            'status' => 'sold',
        ]);

        return $ticket;
    }
}
