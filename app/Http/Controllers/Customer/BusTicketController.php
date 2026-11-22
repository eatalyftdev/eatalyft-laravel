<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Bus\Models\BusTicket;
use Illuminate\Http\Request;

class BusTicketController extends Controller
{
    /**
     * Show ticket details.
     */
    public function show(BusTicket $ticket)
    {
        // Authorization: customer can only view tickets from their own bookings
        if ($ticket->booking->customer_id !== auth()->id()) {
            abort(403, 'You do not have permission to view this ticket.');
        }

        $ticket->load(['booking:id,booking_ref,total_minor,currency', 'busTrip:id,bus_route_id,departure_at,arrival_at', 'busTrip.busRoute:id,origin_city_id,destination_city_id', 'busTrip.busRoute.originCity:id,name_en,name_fr', 'busTrip.busRoute.destinationCity:id,name_en,name_fr', 'busTrip.busTripSeat:id,bus_trip_id,seat_label']);

        return view('customer.tickets.show', compact('ticket'));
    }
}
