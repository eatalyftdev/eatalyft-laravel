<?php

namespace App\Modules\Bus\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bus\Models\BusTrip;
use Illuminate\Http\Request;

class BusDiscoveryController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'to_city_id' => ['nullable', 'integer', 'different:from_city_id', 'exists:cities,id'],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = BusTrip::query()
            ->with(['agency:id,name,slug,logo_url', 'route.stops.city', 'seats:id,bus_trip_id,status'])
            ->whereIn('status', ['scheduled', 'boarding'])
            ->where('departure_at', '>', now());

        if (! empty($validated['date'])) {
            $query->whereDate('departure_at', $validated['date']);
        }
        if (! empty($validated['from_city_id'])) {
            $query->whereHas('route', fn ($q) => $q->where('origin_city_id', $validated['from_city_id']));
        }
        if (! empty($validated['to_city_id'])) {
            $query->whereHas('route', fn ($q) => $q->where('destination_city_id', $validated['to_city_id']));
        }

        return response()->json($query->orderBy('departure_at')->paginate($validated['per_page'] ?? 20));
    }

    public function show(BusTrip $trip)
    {
        abort_unless(in_array($trip->status, ['scheduled', 'boarding'], true), 404);
        return response()->json($trip->load(['agency:id,name,slug,logo_url', 'route.stops.city', 'seats:id,bus_trip_id,seat_label,seat_type,status,price_minor,currency']));
    }
}
