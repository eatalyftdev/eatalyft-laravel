<?php

namespace App\Http\Middleware;

use App\Services\FirebaseAuthService;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Kreait\Firebase\Exception\Auth\InvalidIdToken;

/**
 * Middleware to verify Firebase ID tokens on mobile API routes.
 *
 * The mobile (Expo) client sends a Firebase ID token as a Bearer token.
 * Laravel verifies the token and maps the Firebase UID to a local User
 * record via the `firebase_uid` column.
 *
 * Web session authentication and Sanctum token authentication are NOT
 * affected by this middleware; it is applied only to routes explicitly
 * assigned to the `firebase` guard.
 */
class VerifyFirebaseIdToken
{
    protected FirebaseAuthService $firebaseAuth;

    public function __construct(FirebaseAuthService $firebaseAuth)
    {
        $this->firebaseAuth = $firebaseAuth;
    }

    public function handle(Request $request, Closure $next)
    {
        $idToken = $this->extractBearerToken($request);

        if (empty($idToken)) {
            throw new UnauthorizedHttpException(
                'Firebase',
                'Missing Firebase ID token.',
                [],
                'Bearer realm="firebase", error="missing_token"'
            );
        }

        try {
            $verified = $this->firebaseAuth->verifyIdToken($idToken);
        } catch (InvalidIdToken $e) {
            throw new UnauthorizedHttpException(
                'Firebase',
                'Invalid or expired Firebase ID token.',
                [],
                'Bearer realm="firebase", error="invalid_token"'
            );
        } catch (\Throwable $e) {
            throw new UnauthorizedHttpException(
                'Firebase',
                'Firebase ID token verification failed.',
                [],
                'Bearer realm="firebase", error="verification_failed"'
            );
        }

        $firebaseUid = $this->firebaseAuth->getUidFromToken($verified);
        if (empty($firebaseUid)) {
            throw new UnauthorizedHttpException(
                'Firebase',
                'Firebase ID token contains no user identifier.',
                [],
                'Bearer realm="firebase", error="no_uid"'
            );
        }

        // Check if the Firebase user is disabled (degraded gracefully)
        if ($this->firebaseAuth->isFirebaseUserDisabled($firebaseUid)) {
            throw new UnauthorizedHttpException(
                'Firebase',
                'Firebase user account is disabled.',
                [],
                'Bearer realm="firebase", error="user_disabled"'
            );
        }

        // Find or link the local Laravel user account
        $user = $this->resolveUser($firebaseUid, $verified);
        if (!$user) {
            throw new UnauthorizedHttpException(
                'Firebase',
                'No local account associated with this Firebase identity.',
                [],
                'Bearer realm="firebase", error="no_local_account"'
            );
        }

        // Check account status before allowing access
        if ($user->status && $user->status !== 'active') {
            throw new UnauthorizedHttpException(
                'Firebase',
                'Account is not active.',
                [],
                'Bearer realm="firebase", error="account_inactive"'
            );
        }

        // Attach the verified user to the request for downstream use
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('firebase_user', $user);
        $request->attributes->set('firebase_claims', $verified);

        return $next($request);
    }

    /**
     * Extract the Bearer token from the Authorization header.
     */
    protected function extractBearerToken(Request $request): ?string
    {
        $header = $request->bearerToken();
        if ($header) {
            return $header;
        }

        // Fallback: check X-Firebase-Id-Token header for clients that cannot set Authorization
        return $request->header('X-Firebase-Id-Token');
    }

    /**
     * Resolve the local Laravel user for a Firebase UID.
     *
     * Linking rules:
     * - If a user with this firebase_uid exists, return it.
     * - If no user exists, return null (caller must handle linking).
     * - Never auto-create or auto-merge accounts based on unverified email.
     */
    protected function resolveUser(string $firebaseUid, array $claims): ?User
    {
        // Primary: exact Firebase UID match
        $user = User::where('firebase_uid', $firebaseUid)->first();
        if ($user) {
            return $user;
        }

        // Secondary: check if an email-verified user with the same email exists.
        // Do NOT auto-link based on email alone — require explicit linking flow.
        $email = $claims['email'] ?? null;
        if ($email && ! empty($claims['email_verified'])) {
            $existingUser = User::where('email', $email)->whereNull('firebase_uid')->first();
            if ($existingUser) {
                // Account exists without Firebase UID — caller must initiate linking.
                // We do not auto-link to prevent account takeover via email spoofing.
                return null;
            }
        }

        return null;
    }
}
