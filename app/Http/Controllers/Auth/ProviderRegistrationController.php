<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Service Provider registration controller.
 *
 * Handles provider onboarding with pending verification status.
 * Newly registered providers do NOT automatically gain access to
 * privileged provider operations — administrative approval is required.
 */
class ProviderRegistrationController extends Controller
{
    /**
     * Submit provider registration.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'service_category' => 'required|string|max:50',
            'business_name' => 'nullable|string|max:200',
            'operating_location' => 'required|string|max:200',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted',
        ]);

        // Rate limiting per IP
        $rateLimitKey = 'provider_register:' . $request->ip();
        RateLimiter::forKey($rateLimitKey, 3, 1);

        if (RateLimiter::tooManyAttempts($rateLimitKey)) {
            throw ValidationException::withMessages([
                'email' => __('Too many registration attempts. Please try again later.'),
            ]);
        }

        RateLimiter::hit($rateLimitKey);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'provider',
            'status' => 'pending',
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        // Assign provider role
        $user->assignRole('provider');

        // Log the registration
        \Log::channel('otp')->info('Provider registration submitted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'service_category' => $request->service_category,
            'operating_location' => $request->operating_location,
            'status' => 'pending',
        ]);

        return redirect()->route('login')
            ->with('status', __('Your provider application has been submitted and is pending review. You will be notified once approved.'));
    }
}
