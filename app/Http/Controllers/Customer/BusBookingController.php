<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Bus\Models\BusBooking;
use App\Modules\Bus\Models\BusTrip;
use App\Modules\Bus\Models\BusTripSeat;
use App\Modules\Bus\Services\BusBookingService;
use App\Services\Notification\BookingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusBookingController extends Controller
{
    protected BookingNotificationService $notificationService;

    public function __construct(BusBookingService $busBookingService, BookingNotificationService $notificationService)
    {
        $this->busBookingService = $busBookingService;
        $this->notificationService = $notificationService;
    }
    /**
     * Show seat selection page.
     */
    public function selectSeats(Request $request)
    {
        $request->validate([
            'trip_id' => ['required', 'integer', 'exists:bus_trips,id'],
            'seats' => ['required', 'array', 'min:1', 'max:8'],
            'seats.*' => ['required', 'string', 'max:20'],
        ]);

        $trip = BusTrip::with(['agency:id,name,slug,logo_url', 'route.originCity:id,name_en,name_fr', 'route.destinationCity:id,name_en,name_fr', 'seats:id,bus_trip_id,seat_label,seat_type,status,price_minor,currency'])
            ->findOrFail($request->trip_id);

        abort_unless(in_array($trip->status, ['scheduled', 'boarding'], true), 404);

        // Verify all requested seats are available
        $seatLabels = array_values(array_unique($request->seats));
        $seats = $trip->seats()->whereIn('seat_label', $seatLabels)->lockForUpdate()->get();

        if ($seats->count() !== count($seatLabels)) {
            abort(404, 'One or more seats do not exist.');
        }

        foreach ($seats as $seat) {
            if ($seat->status === 'sold') {
                abort(409, 'Seat ' . $seat->seat_label . ' is already sold.');
            }
            if ($seat->status === 'held' && $seat->hold_expires_at && !$seat->hold_expires_at->isPast()) {
                abort(409, 'Seat ' . $seat->seat_label . ' is temporarily held.');
            }
        }

        return view('customer.bus.select-seats', compact('trip', 'seats', 'seatLabels'));
    }

    /**
     * Store booking (create hold).
     */
    public function store(Request $request, BusBookingService $busBookingService)
    {
        $request->validate([
            'trip_id' => ['required', 'integer', 'exists:bus_trips,id'],
            'seat_labels' => ['required', 'array', 'min:1', 'max:8'],
            'seat_labels.*' => ['required', 'string', 'max:20'],
            'contact_name' => ['required', 'string', 'max:160'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $booking = $busBookingService->holdSeats($request->validated(), $request->user()->id);

            // Notify customer of booking creation
            $this->notificationService->bookingCreated($booking);

            return redirect()->route('bus.bookings.show', $booking)
                ->with('status', 'Seat hold created. Please complete your booking within 10 minutes.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->withErrors(['_error' => 'Failed to create booking hold. Please try again.'])->withInput();
        }
    }

    /**
     * Show booking details.
     */
    public function show(BusBooking $booking)
    {
        // Authorization: customer can only view their own bookings
        if ($booking->customer_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this booking.');
        }

        $booking->load(['agency:id,name,slug', 'busTrip:id,bus_route_id,departure_at,arrival_at', 'busTrip.busRoute:id,origin_city_id,destination_city_id', 'busTrip.busRoute.originCity:id,name_en,name_fr', 'busTrip.busRoute.destinationCity:id,name_en,name_fr', 'busTrip.busTripSeats:id,bus_trip_id,seat_label,seat_type,status,price_minor,currency']);

        return view('customer.bookings.show', compact('booking'));
    }

    /**
     * Cancel a booking.
     */
    public function cancel(BusBooking $booking)
    {
        // Authorization: customer can only cancel their own bookings
        if ($booking->customer_id !== auth()->id()) {
            abort(403, 'You do not have permission to cancel this booking.');
        }

        if (!in_array($booking->status, ['held', 'confirmed'])) {
            return back()->withErrors(['booking' => 'Booking cannot be cancelled.']);
        }

        DB::transaction(function () use ($booking) {
            $booking->update(['status' => 'cancelled']);

            // Release all seats
            foreach ($booking->busTripSeats as $seat) {
                $seat->update([
                    'status' => 'available',
                    'booking_id' => null,
                    'hold_expires_at' => null,
                    'ticket_id' => null,
                ]);
            }

            // Cancel tickets if any
            foreach ($booking->tickets as $ticket) {
                $ticket->update(['status' => 'cancelled']);
            }
        });

        // Notify customer of cancellation
        $this->notificationService->bookingCancelled($booking);

        return redirect()->route('dashboard.bookings')
            ->with('status', 'Booking cancelled successfully.');
    }
}
