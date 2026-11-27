<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class RoleAuthController extends Controller
{
    public function customerLogin()
    {
        return Inertia::render('Auth/CustomerLogin');
    }

    public function customerSignup()
    {
        $cities = \App\Models\City::where('is_enabled', true)->get();
        return Inertia::render('Auth/CustomerSignup', ['cities' => $cities]);
    }

    public function agencyLogin()
    {
        return Inertia::render('Auth/AgencyLogin');
    }

    public function agencySignup()
    {
        $cities = \App\Models\City::where('is_enabled', true)->get();
        return Inertia::render('Auth/AgencySignup', ['cities' => $cities]);
    }

    public function stationAgentLogin()
    {
        return Inertia::render('Auth/StationAgentLogin');
    }

    public function agencyInviteJoin()
    {
        return Inertia::render('Auth/AgencyInviteJoin');
    }

    public function handymanLogin()
    {
        return Inertia::render('Auth/HandymanLogin');
    }

    public function handymanSignup()
    {
        $cities = \App\Models\City::where('is_enabled', true)->get();
        return Inertia::render('Auth/HandymanSignup', ['cities' => $cities, 'specialties' => ['Plumbing', 'Electrical Work', 'Painting', 'Carpentry', 'Tiling', 'AC Repair', 'General Repairs', 'Welding', 'Masonry', 'Cleaning']]);
    }

    public function adminVault()
    {
        return Inertia::render('Auth/AdminVaultLogin');
    }
}