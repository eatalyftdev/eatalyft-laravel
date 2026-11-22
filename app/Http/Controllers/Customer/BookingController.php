<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Bus\Models\BusBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * Show customer's bookings list.
     */
    public function index(Request $request)
    {
        $query = BusBooking::where('customer_id', auth()->id())
            ->with(['busTrip', 'agency']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_ref', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('customer.bookings.index', compact('bookings'));
    }

    /**
     * Show booking detail.
     */
    public function show(BusBooking $booking)
    {
        if ($booking->customer_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this booking.');
        }

        $booking->load(['busTrip', 'agency', 'busTripSeats', 'busTrip.route.originCity', 'busTrip.busRoute.destinationCity']);

        return view('customer.bookings.show', compact('booking'));
    }
}
