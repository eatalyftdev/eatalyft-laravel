<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Bus\Models\BusTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * Show customer's tickets list.
     */
    public function index(Request $request)
    {
        $query = BusTicket::whereHas('booking', function ($query) {
            $query->where('customer_id', auth()->id());
        })->with(['busTrip', 'busTripSeat', 'booking']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('passenger_name', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('customer.tickets.index', compact('tickets'));
    }

    /**
     * Show ticket detail.
     */
    public function show(BusTicket $ticket)
    {
        if ($ticket->booking->customer_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this ticket.');
        }

        $ticket->load(['busTrip', 'busTripSeat', 'booking', 'busTrip.route.originCity', 'busTrip.busRoute.destinationCity', 'busTrip.agency']);

        return view('customer.tickets.show', compact('ticket'));
    }
}
