<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Modules\Bus\Models\BusBooking;
use App\Modules\Bus\Models\BusTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show customer dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        $upcomingTrips = BusBooking::where('customer_id', $user->id)
            ->whereIn('status', ['held', 'confirmed'])
            ->with(['busTrip', 'agency'])
            ->whereHas('busTrip', function ($query) {
                $query->where('departure_at', '>', now());
            })
            ->orderBy('busTrip.departure_at')
            ->limit(5)
            ->get();

        $totalBookings = BusBooking::where('customer_id', $user->id)->count();
        $activeTickets = BusTicket::whereHas('booking', function ($query) {
            $query->where('customer_id', $user->id);
        })->where('status', 'issued')->count();

        $recentBookings = BusBooking::where('customer_id', $user->id)
            ->with(['busTrip', 'agency'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('customer.dashboard.index', compact(
            'upcomingTrips',
            'totalBookings',
            'activeTickets',
            'recentBookings'
        ));
    }
}
