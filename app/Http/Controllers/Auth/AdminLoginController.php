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
 * Admin login controller.
 *
 * Provides a dedicated authentication boundary for administrator access.
 * Administrator login verifies credentials, checks account status,
 * enforces administrator role authorization, and applies rate limiting.
 *
 * Administrator registration is NOT available publicly — accounts are
 * created only through the AdminUserSeeder or controlled provisioning.
 */
class AdminLoginController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLogin()
    {
        return view('auth.admin-login');
    }

    /**
     * Authenticate an administrator.
     *
     * Validates credentials, verifies account status, enforces administrator
     * role, and applies rate limiting to prevent brute-force attacks.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $rateLimitKey = 'admin_login:' . $request->input('email');

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
            ]);
        }

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($rateLimitKey, 60);

            throw ValidationException::withMessages([
                'email' => __('The provided credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Verify account status
        if ($user->status && $user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('Your account is not active. Please contact support.'),
            ]);
        }

        // Verify administrator role — customers, providers, and agencies are rejected
        if (!$user->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('Administrator access required.'),
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        return redirect()->intended(route('dashboard'))
            ->with('status', __('Welcome back, :name.', ['name' => $user->name]));
    }
}
