<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Modules\Bus\Models\BusBooking;
use App\Modules\Bus\Models\BusTrip;
use App\Modules\Bus\Models\BusTripSeat;
use App\Modules\Bus\Models\BusTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BusController extends Controller
{
    /**
     * Agency dashboard - show agency's trips and stats.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $agency = $user->agency;

        if (!$agency) {
            abort(403, 'You are not associated with any agency.');
        }

        $trips = BusTrip::where('agency_id', $agency->id)
            ->with(['busRoute', 'busTripSeats'])
            ->where('departure_at', '>=', now())
            ->orderBy('departure_at')
            ->paginate(10);

        $stats = [
            'total_trips' => BusTrip::where('agency_id', $agency->id)->count(),
            'active_trips' => BusTrip::where('agency_id', $agency->id)->whereIn('status', ['scheduled', 'boarding'])->count(),
            'total_bookings' => BusBooking::where('agency_id', $agency->id)->count(),
            'held_seats' => BusTripSeat::whereHas('busTrip', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->where('status', 'held')->count(),
            'sold_seats' => BusTripSeat::whereHas('busTrip', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->where('status', 'sold')->count(),
        ];

        return view('agency.bus.dashboard', compact('trips', 'stats', 'agency'));
    }

    /**
     * Show trip detail for agency.
     */
    public function tripShow(BusTrip $trip)
    {
        $agency = auth()->user()->agency;

        if ($trip->agency_id !== $agency->id) {
            abort(403, 'You do not have permission to view this trip.');
        }

        $trip->load(['busRoute.originCity', 'busRoute.destinationCity', 'busTripSeats', 'busTripSeats.booking']);

        return view('agency.bus.trip-show', compact('trip'));
    }

    /**
     * Lookup booking by reference.
     */
    public function bookingLookup(Request $request)
    {
        $request->validate([
            'booking_ref' => ['required', 'string', 'max:40'],
        ]);

        $booking = BusBooking::where('booking_ref', $request->booking_ref)
            ->where('agency_id', auth()->user()->agency_id)
            ->first();

        if (!$booking) {
            return back()->withErrors(['booking_ref' => 'Booking not found.']);
        }

        $booking->load(['busTrip', 'busTripSeats', 'customer']);

        return view('agency.bus.booking-show', compact('booking'));
    }

    /**
     * Scan ticket (validate QR).
     */
    public function scanTicket(Request $request)
    {
        $request->validate([
            'ticket_number' => ['required', 'string', 'max:48'],
        ]);

        $ticket = \App\Modules\Bus\Models\BusTicket::where('ticket_number', $request->ticket_number)
            ->whereHas('busTrip', function ($q) {
                $q->where('agency_id', auth()->user()->agency_id);
            })
            ->first();

        if (!$ticket) {
            return back()->withErrors(['ticket_number' => 'Ticket not found or not authorized for this agency.']);
        }

        if ($ticket->status !== 'issued') {
            return back()->withErrors(['ticket_number' => 'Ticket is not valid for boarding.']);
        }

        // Mark ticket as used (scanned)
        $ticket->update([
            'status' => 'used',
            'scanned_at' => now(),
            'scanned_by' => auth()->id(),
        ]);

        \App\Modules\Bus\Models\BusTicketEvent::create([
            'bus_ticket_id' => $ticket->id,
            'actor_user_id' => auth()->id(),
            'event_type' => 'scanned',
            'payload' => [
                'scanned_at' => now()->toISOString(),
                'scanned_by' => auth()->id(),
                'scan_location' => 'agency_checkpoint',
            ],
            'source' => 'scanner',
        ]);

        return back()->with('status', 'Ticket ' . $ticket->ticket_number . ' scanned successfully.');
    }
}
