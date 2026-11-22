<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Show customer notifications.
     */
    public function index(Request $request)
    {
        // For now, return empty - notifications will be implemented fully in Phase 10
        $notifications = [];

        return view('customer.notifications.index', compact('notifications'));
    }
}
