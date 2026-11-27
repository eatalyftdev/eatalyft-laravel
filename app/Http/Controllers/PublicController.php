<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\City;
use App\Models\Menu;
use App\Models\Partner;
use App\Modules\Bus\Models\BusRoute;
use App\Modules\Bus\Models\BusRouteStop;
use App\Modules\Bus\Models\BusTrip;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicController extends Controller
{
    public function home()
    {
        $cities = \App\Models\City::where('is_enabled', true)->get();
        $corridors = [
            ['from' => 'Douala', 'to' => 'Yaoundé', 'fare' => 5000],
            ['from' => 'Yaoundé', 'to' => 'Bamenda', 'fare' => 6000],
            ['from' => 'Douala', 'to' => 'Bafoussam', 'fare' => 4500],
        ];

        return \Inertia\Inertia::render('Welcome', [
            'cities' => $cities,
            'corridors' => $corridors,
            'zigoDispatchUrl' => config('services.zigo.dispatch_url', env('ZIGO_DISPATCH_URL', '')),
        ]);
    }

    public function legacyHome()
    {
        $partners = Partner::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('public.home', compact('partners'));
    }

    public function search(Request $request)
    {
        $service = $request->get('service');
        $query = $request->get('q');

        // TODO: Implement actual search logic
        return view('public.search', compact('service', 'query'));
    }

    public function busSearch(Request $request)
    {
        $validated = $request->validate([
            'from_city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'to_city_id' => ['sometimes', 'integer', 'exists:cities,id', 'different:from_city_id'],
            'date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:today'],
            'passengers' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        $query = BusTrip::query()
            ->with(['agency:id,name,slug,logo_url', 'route.originCity:id,name_en,name_fr', 'route.destinationCity:id,name_en,name_fr', 'seats:id,bus_trip_id,seat_label,seat_type,status,price_minor,currency'])
            ->whereIn('status', ['scheduled', 'boarding'])
            ->where('departure_at', '>', now());

        if (! empty($validated['from_city_id'])) {
            $query->whereHas('route', fn ($q) => $q->where('origin_city_id', $validated['from_city_id']));
        }

        if (! empty($validated['to_city_id'])) {
            $query->whereHas('route', fn ($q) => $q->where('destination_city_id', $validated['to_city_id']));
        }

        if (! empty($validated['date'])) {
            $query->whereDate('departure_at', $validated['date']);
        }

        $trips = $query->orderBy('departure_at')->paginate(20)->withQueryString();

        $cities = City::orderBy('name_en')->get();

        return view('public.bus.search', compact('trips', 'cities', 'validated'));
    }

    public function busTripShow(BusTrip $trip)
    {
        abort_unless(in_array($trip->status, ['scheduled', 'boarding'], true), 404);

        $trip->load([
            'agency:id,name,slug,logo_url',
            'route.originCity:id,name_en,name_fr',
            'route.destinationCity:id,name_en,name_fr',
            'route.stops:id,bus_route_id,city_id,stop_order,offset_minutes',
            'seats:id,bus_trip_id,seat_label,seat_type,status,price_minor,currency',
        ]);

        $routeStops = $trip->route->stops()->with('city:id,name_en,name_fr')->orderBy('stop_order')->get();

        return view('public.bus.show', compact('trip', 'routeStops'));
    }

    public function services()
    {
        return view('public.services.index');
    }

    public function serviceShow($slug)
    {
        // TODO: Fetch service from database/CMS
        $services = [
            'bus_tickets' => [
                'title' => __('Bus tickets'),
                'description' => __('Intercity and local buses with seat selection and instant e-tickets.'),
                'content' => '<h2>'.__('Overview').'</h2><p>'.__('Book bus tickets across 500+ routes with real-time seat selection.').'</p>',
            ],
            'flights' => [
                'title' => __('Flights'),
                'description' => __('Domestic and international flights with flexible booking options.'),
                'content' => '<h2>'.__('Overview').'</h2><p>'.__('Search and book flights from 300+ airlines.').'</p>',
            ],
            'esim' => [
                'title' => __('eSIM'),
                'description' => __('Instant data connectivity in 190+ countries. No physical SIM needed.'),
                'content' => '<h2>'.__('Overview').'</h2><p>'.__('Stay connected worldwide with instant eSIM activation.').'</p>',
            ],
            'gift_cards' => [
                'title' => __('Gift cards'),
                'description' => __('Give the gift of travel. No expiry, usable across all services.'),
                'content' => '<h2>'.__('Overview').'</h2><p>'.__('Purchase digital gift cards for any occasion.').'</p>',
            ],
            'hotels' => [
                'title' => __('Hotels'),
                'description' => __('Curated stays with free cancellation on most bookings.'),
                'content' => '<h2>'.__('Overview').'</h2><p>'.__('Book from 1M+ hotels worldwide.').'</p>',
            ],
            'handyman' => [
                'title' => __('Handyman'),
                'description' => __('Verified professionals for repairs, installations, and maintenance.'),
                'content' => '<h2>'.__('Overview').'</h2><p>'.__('Book trusted handymen for same-day service.').'</p>',
            ],
            'nanny' => [
                'title' => __('Nanny'),
                'description' => __('Background-checked caregivers available on your schedule.'),
                'content' => '<h2>'.__('Overview').'</h2><p>'.__('Find verified nannies and babysitters.').'</p>',
            ],
        ];

        $service = $services[$slug] ?? null;

        if (!$service) {
            abort(404);
        }

        $service = (object) $service;

        return view('public.services.show', compact('service', 'slug'));
    }

    public function about()
    {
        return view('public.about');
    }

    public function agencies()
    {
        $agencies = Agency::where('is_active', true)
            ->with('city')
            ->orderBy('name')
            ->paginate(12);

        return view('public.agencies', compact('agencies'));
    }

    public function faq()
    {
        return view('public.faq');
    }

    public function terms()
    {
        return view('public.legal.terms');
    }

    public function privacy()
    {
        return view('public.legal.privacy');
    }

    public function cookies()
    {
        return view('public.legal.cookies');
    }
}