<?php

namespace App\Modules\Bus\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bus\Models\BusBooking;
use App\Modules\Bus\Services\BusBookingService;
use Illuminate\Http\Request;

class BusBookingApiController extends Controller
{
    public function store(Request $request, BusBookingService $bookings)
    {
        $data = $request->validate([
            'trip_id' => ['required', 'integer', 'exists:bus_trips,id'],
            'seat_labels' => ['required', 'array', 'min:1', 'max:8'],
            'seat_labels.*' => ['required', 'string', 'max:20'],
            'contact_name' => ['required', 'string', 'max:160'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $booking = $bookings->holdSeats($data, $request->user()->id);

        return response()->json([
            'message' => 'Seats held temporarily. No payment has been collected.',
            'booking' => $booking,
        ], 201);
    }

    public function mine(Request $request)
    {
        return response()->json(BusBooking::query()
            ->with(['tickets', 'agency:id,name,slug'])
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate(20));
    }
}
