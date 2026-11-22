<?php

namespace App\Services;

use Kreait\Firebase\Exception\Auth\InvalidIdToken;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Verifies Firebase-issued ID tokens for mobile (Expo) authentication.
 *
 * Never trusts client-provided UID, email, roles, or permissions.
 * Laravel remains the authorization authority; Firebase only proves identity.
 */
class FirebaseAuthService
{
    protected FirebaseAuth $firebaseAuth;
    protected string $projectId;

    public function __construct()
    {
        $factory = (new Factory())
            ->withServiceAccount(config('firebase.credentials'))
            ->withProjectId(config('firebase.project_id'));

        $this->firebaseAuth = $factory->createAuth();
        $this->projectId = config('firebase.project_id');
    }

    /**
     * Verify a Firebase ID token and return the decoded claims.
     *
     * @throws InvalidIdToken When the token is invalid, expired, or revoked.
     * @throws \Exception When the token cannot be verified.
     */
    public function verifyIdToken(string $idToken): array
    {
        if (empty($idToken)) {
            throw new InvalidArgumentException('ID token is required.');
        }

        try {
            $verifiedToken = $this->firebaseAuth->verifyIdToken($idToken);

            // Explicitly verify required claims (the SDK does this, but we assert defensively)
            $this->assertRequiredClaims($verifiedToken);

            Log::channel('otp')->debug('Firebase ID token verified', [
                'uid' => $verifiedToken['uid'] ?? null,
                'iss' => $verifiedToken['iss'] ?? null,
                'aud' => $verifiedToken['aud'] ?? null,
            ]);

            return $verifiedToken;
        } catch (InvalidIdToken $e) {
            Log::channel('otp')->warning('Firebase ID token invalid', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::channel('otp')->error('Firebase ID token verification failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Extract the Firebase UID from a verified token without re-verifying.
     * Only call after verifyIdToken() has succeeded.
     */
    public function getUidFromToken(array $verifiedToken): ?string
    {
        return $verifiedToken['uid'] ?? null;
    }

    /**
     * Check whether a Firebase user is disabled (requires Admin SDK auth).
     * Returns false if the check cannot be performed (degraded gracefully).
     */
    public function isFirebaseUserDisabled(string $uid): bool
    {
        try {
            $userRecord = $this->firebaseAuth->getUser($uid);
            return $userRecord->disabled ?? false;
        } catch (\Throwable $e) {
            Log::channel('otp')->warning('Could not check Firebase user status', [
                'uid' => $uid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Assert that the verified token contains all required claims.
     */
    protected function assertRequiredClaims(array $token): void
    {
        $required = ['iss', 'aud', 'iat', 'exp', 'uid', 'sub'];
        foreach ($required as $claim) {
            if (! array_key_exists($claim, $token)) {
                throw new InvalidIdToken("Missing required claim: {$claim}");
            }
        }

        // Verify issuer matches expected Firebase pattern
        $expectedIssuer = "https://securetoken.google.com/{$this->projectId}";
        if ($token['iss'] !== $expectedIssuer) {
            throw new InvalidIdToken('Token issuer does not match expected Firebase project.');
        }

        // Verify audience matches the project ID
        if ($token['aud'] !== $this->projectId) {
            throw new InvalidIdToken('Token audience does not match expected Firebase project.');
        }

        // Verify expiration
        $now = time();
        if (! empty($token['exp']) && $token['exp'] < $now) {
            throw new InvalidIdToken('Token has expired.');
        }

        // Verify issued-at is not in the future (clock tolerance of 5 minutes)
        if (! empty($token['iat']) && $token['iat'] > $now + 300) {
            throw new InvalidIdToken('Token issued-at is in the future.');
        }

        // UID and sub must match
        if ($token['uid'] !== $token['sub']) {
            throw new InvalidIdToken('Token UID and subject do not match.');
        }
    }
}
