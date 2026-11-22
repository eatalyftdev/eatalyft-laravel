<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Web: Show login form
    public function showLogin()
    {
        return view('auth.login');
    }

    // Web: Show register form (customer)
    public function showRegisterCustomer()
    {
        return view('auth.register-customer');
    }

    // Web: Show register form (provider)
    public function showRegisterProvider()
    {
        return view('auth.register-provider');
    }

    // Web: Show register form (agency)
    public function showRegisterAgency()
    {
        return view('auth.register-agency');
    }

    // Web: Show register form
    public function showRegister()
    {
        return view('auth.register');
    }

    // Web: Show forgot password form
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // Web: Show reset password form
    public function showResetPassword($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    // Web: Show email verification notice
    public function showVerifyEmail()
    {
        return view('auth.verify-email');
    }

    // Web: Show confirm password form
    public function showConfirmPassword()
    {
        return view('auth.confirm-password');
    }

    // Web: Request OTP for login
    public function loginOtpRequest(Request $request, OtpService $otpService)
    {
        $request->validate([
            'contact' => 'required|string',
        ]);

        $contact = $request->contact;
        $channel = filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms';
        $purpose = 'login';

        try {
            $result = $otpService->request($channel, $contact, $purpose, $request->ip(), $request->header('X-Device-Id'));
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Code sent',
                    'code' => config('otp.show_code_on_screen') ? ($result['code'] ?? null) : null,
                ]);
            }

            return redirect()->route('login')
                ->with('status', 'otp_sent')
                ->with('contact', $contact);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Code sent']);
            }
            return back()->withErrors(['contact' => 'Failed to send code. Please try again.']);
        }
    }

    // Web: Verify OTP for login
    public function loginOtpVerify(Request $request, OtpService $otpService)
    {
        \Log::channel('single')->info('loginOtpVerify request', ['input' => $request->all()]);
        $request->validate([
            'contact' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $contact = $request->contact;
        $code = $request->otp;

        if (!$otpService->verify($contact, $code, 'login')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid or expired code'], 422);
            }
            return back()->withErrors(['otp' => 'Invalid or expired code.'])->withInput();
        }

        // Find user by email or phone
        $user = User::where('email', $contact)->orWhere('phone', $contact)->first();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'User not found'], 404);
            }
            return back()->withErrors(['contact' => 'No account found with this contact.']);
        }

        Auth::login($user, true);

        if ($request->expectsJson()) {
            $token = $user->createToken('web')->plainTextToken;
            return response()->json(['user' => $user, 'token' => $token]);
        }

        return redirect()->intended(route('dashboard'));
    }

    // Web: Request OTP for registration
    public function registerOtpRequest(Request $request, OtpService $otpService)
    {
        $request->validate([
            'contact' => 'required|string',
        ]);

        $contact = $request->contact;
        $channel = filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms';
        $purpose = 'register';

        // Check if contact already exists
        $existingUser = User::where('email', $contact)->orWhere('phone', $contact)->first();
        if ($existingUser) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'An account with this contact already exists'], 422);
            }
            return back()->withErrors(['contact' => 'An account with this contact already exists.']);
        }

        try {
            $result = $otpService->request($channel, $contact, $purpose, $request->ip(), $request->header('X-Device-Id'));
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Code sent',
                    'code' => config('otp.show_code_on_screen') ? ($result['code'] ?? null) : null,
                ]);
            }

            return redirect()->route('register')
                ->with('status', 'otp_sent')
                ->with('contact', $contact);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Code sent']);
            }
            return back()->withErrors(['contact' => 'Failed to send code. Please try again.']);
        }
    }

    // Web: Verify OTP for registration
    public function registerOtpVerify(Request $request, OtpService $otpService)
    {
        $request->validate([
            'contact' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        $contact = $request->contact;
        $code = $request->otp;

        if (!$otpService->verify($contact, $code, 'register')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid or expired code'], 422);
            }
            return back()->withErrors(['otp' => 'Invalid or expired code.'])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Contact verified', 'contact' => $contact]);
        }

        // Redirect to registration completion form with verified contact
        return redirect()->route('register')
            ->with('status', 'contact_verified')
            ->with('verified_contact', $contact);
    }

    // Web: Complete registration with name/password
    public function registerComplete(Request $request)
    {
        $request->validate([
            'contact' => 'required|string',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $contact = $request->contact;
        $email = filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null;
        $phone = !filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null;

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $request->name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'email_verified_at' => $email ? now() : null,
            'phone_verified_at' => $phone ? now() : null,
        ]);

        $user->assignRole('customer');

        Auth::login($user, true);

        if ($request->expectsJson()) {
            $token = $user->createToken('web')->plainTextToken;
            return response()->json(['user' => $user, 'token' => $token]);
        }

        return redirect()->route('dashboard');
    }

    // Web: Forgot password (request reset OTP)
    public function forgotPassword(Request $request, OtpService $otpService)
    {
        $request->validate([
            'contact' => 'required|string',
        ]);

        $contact = $request->contact;
        $channel = filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms';
        $purpose = 'password_reset';

        $user = User::where('email', $contact)->orWhere('phone', $contact)->first();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'If an account exists, a reset code has been sent']);
            }
            return back()->with('status', 'If an account exists, a reset code has been sent');
        }

        try {
            $result = $otpService->request($channel, $contact, $purpose, $request->ip(), $request->header('X-Device-Id'));
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'If an account exists, a reset code has been sent',
                    'code' => config('otp.show_code_on_screen') ? ($result['code'] ?? null) : null,
                ]);
            }

            return back()->with('status', 'If an account exists, a reset code has been sent');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'If an account exists, a reset code has been sent']);
            }
            return back()->with('status', 'If an account exists, a reset code has been sent');
        }
    }

    // Web: Reset password with OTP
    public function resetPassword(Request $request, OtpService $otpService)
    {
        $request->validate([
            'contact' => 'required|string',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $contact = $request->contact;
        $code = $request->otp;

        if (!$otpService->verify($contact, $code, 'password_reset')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Invalid or expired code'], 422);
            }
            return back()->withErrors(['otp' => 'Invalid or expired code.'])->withInput();
        }

        $user = User::where('email', $contact)->orWhere('phone', $contact)->first();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'User not found'], 404);
            }
            return back()->withErrors(['contact' => 'User not found.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Password reset successful']);
        }

        return redirect()->route('login')->with('status', 'Password has been reset. Please sign in.');
    }

    // Web: Email verification
    public function verifyEmail(Request $request)
    {
        $request->user()->markEmailAsVerified();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Email verified']);
        }

        return redirect()->route('dashboard')->with('status', 'Email verified successfully');
    }

    // Web: Send verification email
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Email already verified']);
            }
            return back()->with('status', 'Email already verified');
        }

        $request->user()->sendEmailVerificationNotification();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Verification link sent']);
        }

        return back()->with('status', 'Verification link sent');
    }

    // Web: Confirm password
    public function confirmPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Password confirmed']);
        }

        return back()->with('status', 'Password confirmed');
    }

    // Web: Update password
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $request->user()->password = Hash::make($request->password);
        $request->user()->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Password updated']);
        }

        return back()->with('status', 'Password updated successfully');
    }

    // API: Get authenticated user
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // API: Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    // API: Logout all devices
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out all']);
    }

    // Web: Logout
    public function logoutWeb(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    // API: Request OTP (login or register)
    public function requestOtp(Request $request, OtpService $otpService)
    {
        \Log::channel('single')->info('requestOtp called', [
            'show_code_on_screen' => config('otp.show_code_on_screen'),
            'input' => $request->all(),
        ]);
        $request->validate([
            'channel' => 'required|in:email,sms,whatsapp',
            'destination' => 'required|string',
            'purpose' => 'required|in:login,register,password_reset',
        ]);

        $channel = $request->channel;
        $destination = $request->destination;
        $purpose = $request->purpose;

        try {
            $result = $otpService->request($channel, $destination, $purpose, $request->ip(), $request->header('X-Device-Id'));
            \Log::channel('single')->info('requestOtp result', [
                'show_code_on_screen' => config('otp.show_code_on_screen'),
                'has_code_in_result' => isset($result['code']),
                'code_value' => $result['code'] ?? null,
            ]);
            return response()->json([
                'message' => 'Code sent',
                'code' => config('otp.show_code_on_screen') ? ($result['code'] ?? null) : null,
            ]);
        } catch (\RuntimeException $e) {
            \Log::channel('single')->error('requestOtp RuntimeException', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            if (in_array($e->getMessage(), ['Too many requests', 'Resend cooldown'])) {
                return response()->json(['message' => $e->getMessage()], 429);
            }
            // Debug: return exception message
            return response()->json(['message' => 'Code sent', 'debug_exception' => $e->getMessage()], 500);
        } catch (\Throwable $e) {
            \Log::channel('single')->error('requestOtp Throwable', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Debug: return exception message
            return response()->json(['message' => 'Code sent', 'debug_exception' => $e->getMessage()], 500);
        }
}
    
    // API: Verify OTP (login or register)
    public function verifyOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'channel' => 'sometimes|in:email,sms,whatsapp',
            'destination' => 'required|string',
            'purpose' => 'required|in:login,register,password_reset',
            'code' => 'required|string|size:6',
        ]);

        $destination = $request->destination;
        $code = $request->code;
        $purpose = $request->purpose;
        $channel = $request->channel ?? (filter_var($destination, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms');

        if (!$otpService->verify($destination, $code, $purpose)) {
            return response()->json(['message' => 'Invalid or expired code'], 422);
        }

        if ($purpose === 'login') {
            $user = User::where('email', $destination)->orWhere('phone', $destination)->first();
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            $token = $user->createToken('api')->plainTextToken;
            return response()->json(['user' => $user, 'token' => $token]);
        }

        if ($purpose === 'register') {
            return response()->json(['message' => 'Verified']);
        }

        return response()->json(['message' => 'OTP verified']);
    }

    // API: Complete registration
    public function completeRegister(Request $request)
    {
        $request->validate([
            'contact' => 'required|string',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $contact = $request->contact;
        $email = filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null;
        $phone = !filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : null;

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $request->name,
            'email' => $email,
            'phone' => $phone,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'email_verified_at' => $email ? now() : null,
            'phone_verified_at' => $phone ? now() : null,
        ]);

        $user->assignRole('customer');

        $token = $user->createToken('api')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token]);
    }

    // API: Login with OTP
    public function loginOtp(Request $request, OtpService $otpService)
    {
        $request->validate([
            'contact' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $contact = $request->contact;
        $code = $request->code;

        if (!$otpService->verify($contact, $code, 'login')) {
            return response()->json(['message' => 'Invalid or expired code'], 422);
        }

        $user = User::where('email', $contact)->orWhere('phone', $contact)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $token = $user->createToken('api')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token]);
    }
}