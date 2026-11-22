<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FirebaseAuthService;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Auth\InvalidIdToken;

/**
 * Firebase mobile authentication controller.
 *
 * Handles:
 * 1. Firebase ID token verification → local user lookup → Sanctum token issuance
 * 2. Account linking: existing Laravel user + Firebase identity
 *
 * Web session authentication is NOT affected by this controller.
 */
class FirebaseAuthController extends Controller
{
    protected FirebaseAuthService $firebaseAuth;
    protected OtpService $otpService;

    public function __construct(FirebaseAuthService $firebaseAuth, OtpService $otpService)
    {
        $this->firebaseAuth = $firebaseAuth;
        $this->otpService = $otpService;
    }

    /**
     * Verify a Firebase ID token and authenticate the corresponding local user.
     *
     * Request body: { id_token: string }
     * Response: { user, token } where token is a Sanctum plain-text token.
     */
    public function login(Request $request)
    {
        RateLimiter::forKey($request->ip(), 10, 1); // 10 attempts per minute per IP

        if (RateLimiter::tooManyAttempts($request->ip())) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $request->validate([
            'id_token' => 'required|string',
        ]);

        RateLimiter::hit($request->ip());

        try {
            $verified = $this->firebaseAuth->verifyIdToken($request->id_token);
        } catch (InvalidIdToken $e) {
            return response()->json([
                'message' => 'Invalid or expired Firebase ID token.',
            ], 401);
        } catch (\Throwable $e) {
            Log::channel('otp')->error('Firebase login token verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Firebase ID token verification failed.',
            ], 401);
        }

        $firebaseUid = $verified['uid'] ?? null;
        if (empty($firebaseUid)) {
            return response()->json([
                'message' => 'Firebase ID token contains no user identifier.',
            ], 401);
        }

        // Resolve local user by Firebase UID
        $user = User::where('firebase_uid', $firebaseUid)->first();

        if (!$user) {
            // No local account linked to this Firebase identity.
            // Check if an email-verified user exists without a Firebase UID.
            $email = $verified['email'] ?? null;
            if ($email && ! empty($verified['email_verified'])) {
                $existingUser = User::where('email', $email)->whereNull('firebase_uid')->first();
                if ($existingUser) {
                    return response()->json([
                        'message' => 'An account with this email exists but is not linked to Firebase. Please link accounts first.',
                        'error' => 'account_not_linked',
                        'email' => $email,
                    ], 409);
                }
            }

            return response()->json([
                'message' => 'No local account associated with this Firebase identity.',
                'error' => 'no_local_account',
            ], 401);
        }

        // Check account status
        if ($user->status && $user->status !== 'active') {
            return response()->json([
                'message' => 'Account is not active.',
            ], 403);
        }

        // Issue Sanctum token for API access
        $token = $user->createToken('firebase-mobile')->plainTextToken;

        // Log the authentication (audit)
        Log::channel('otp')->info('Firebase mobile login', [
            'user_id' => $user->id,
            'firebase_uid' => $firebaseUid,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Authenticated',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'role', 'status']),
            'token' => $token,
        ]);
    }

    /**
     * Link a Firebase identity to an existing Laravel account.
     *
     * Request body: {
     *   id_token: string,        // Firebase ID token
     *   contact: string,         // Existing user email or phone
     *   otp: string,             // OTP code to prove ownership of the Laravel account
     * }
     *
     * Response: { user, token }
     */
    public function link(Request $request)
    {
        RateLimiter::forKey($request->ip(), 5, 1); // 5 attempts per minute per IP

        if (RateLimiter::tooManyAttempts($request->ip())) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $request->validate([
            'id_token' => 'required|string',
            'contact' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        RateLimiter::hit($request->ip());

        try {
            $verified = $this->firebaseAuth->verifyIdToken($request->id_token);
        } catch (InvalidIdToken $e) {
            return response()->json([
                'message' => 'Invalid or expired Firebase ID token.',
            ], 401);
        } catch (\Throwable $e) {
            Log::channel('otp')->error('Firebase link token verification failed', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Firebase ID token verification failed.',
            ], 401);
        }

        $firebaseUid = $verified['uid'] ?? null;
        if (empty($firebaseUid)) {
            return response()->json([
                'message' => 'Firebase ID token contains no user identifier.',
            ], 401);
        }

        // The Firebase UID must not already be linked to a different account
        $existingByUid = User::where('firebase_uid', $firebaseUid)->first();
        if ($existingByUid) {
            return response()->json([
                'message' => 'This Firebase identity is already linked to another account.',
                'error' => 'uid_already_linked',
            ], 409);
        }

        // Find the local Laravel account
        $contact = $request->contact;
        $user = User::where('email', $contact)
            ->orWhere('phone', $contact)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'No account found with this contact.',
            ], 404);
        }

        // The local account must not already have a Firebase UID linked
        if (! empty($user->firebase_uid)) {
            return response()->json([
                'message' => 'This account already has a Firebase identity linked.',
                'error' => 'firebase_uid_already_set',
            ], 409);
        }

        // Verify OTP to prove ownership of the Laravel account
        $otpPurpose = filter_var($contact, FILTER_VALIDATE_EMAIL) ? 'email' : 'sms';
        if (!$this->otpService->verify($contact, $request->otp, 'password_reset')) {
            return response()->json([
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        // Link the Firebase UID to the local account (transactional)
        $user->update(['firebase_uid' => $firebaseUid]);

        // Issue Sanctum token
        $token = $user->createToken('firebase-mobile')->plainTextToken;

        Log::channel('otp')->info('Firebase account linked', [
            'user_id' => $user->id,
            'firebase_uid' => $firebaseUid,
            'contact' => $contact,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Account linked successfully',
            'user' => $user->only(['id', 'name', 'email', 'phone', 'role', 'status']),
            'token' => $token,
        ]);
    }
}
