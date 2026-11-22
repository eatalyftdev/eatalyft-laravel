<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Transport Agency registration controller.
 *
 * Handles agency onboarding with pending review status.
 * Newly registered agencies do NOT automatically grant
 * unrestricted agency-management permissions — administrative
 * approval is required.
 */
class AgencyRegistrationController extends Controller
{
    /**
     * Submit agency registration.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'agency_name' => 'required|string|max:200',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'headquarters_city' => 'required|string|max:100',
            'contact_person_name' => 'required|string|max:200',
            'contact_person_phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted',
        ]);

        // Rate limiting per IP
        $rateLimitKey = 'agency_register:' . $request->ip();
        RateLimiter::forKey($rateLimitKey, 3, 1);

        if (RateLimiter::tooManyAttempts($rateLimitKey)) {
            throw ValidationException::withMessages([
                'email' => __('Too many registration attempts. Please try again later.'),
            ]);
        }

        RateLimiter::hit($rateLimitKey);

        // Find or create city record
        $city = City::firstOrCreate(
            ['name' => $request->headquarters_city],
            ['country' => 'CM', 'is_active' => true]
        );

        // Create the agency with pending status
        $slug = Str::slug($request->agency_name);
        $agency = Agency::create([
            'name' => $request->agency_name,
            'slug' => $slug,
            'city_id' => $city->id,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => 'pending',
            'is_active' => false,
        ]);

        // Create the agency manager user
        $user = User::create([
            'first_name' => $request->contact_person_name,
            'last_name' => '',
            'name' => $request->contact_person_name,
            'email' => $request->email,
            'phone' => $request->contact_person_phone,
            'password' => Hash::make($request->password),
            'role' => 'agency_manager',
            'agency_id' => $agency->id,
            'status' => 'pending',
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        // Assign agency_manager role
        $user->assignRole('agency_manager');

        // Log the registration
        \Log::channel('otp')->info('Agency registration submitted', [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'agency_name' => $agency->name,
            'email' => $agency->email,
            'headquarters_city' => $request->headquarters_city,
            'status' => 'pending',
        ]);

        return redirect()->route('login')
            ->with('status', __('Your agency application has been submitted and is pending review. You will be notified once approved.'));
    }
}
