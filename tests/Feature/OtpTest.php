<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\OtpCode;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Config::set('otp.show_code_on_screen', true);
        Config::set('otp.max_attempts', 5);
    }

    /** @test */
    public function otp_request_success_returns_code_in_local(): void
    {
        Config::set('otp.max_attempts', 5);
        Config::set('otp.show_code_on_screen', true);
        
        $response = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);

        // Debug: dump response and check for exception
        if ($response->status() === 200) {
            $json = $response->json();
            if (!isset($json['code'])) {
                // Try to see what happened by checking the logs
                // The issue is likely an exception in LogSender
                $this->fail('No code in response. Full response: ' . json_encode($json));
            }
        } else {
            $this->fail('Response status: ' . $response->status() . ' Body: ' . $response->getContent());
        }
        
        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'code'])
            ->assertJson(['message' => 'Code sent']);
        
        $this->assertNotNull($response->json('code'));
        $this->assertEquals(6, strlen($response->json('code')));
    }

    /** @test */
    public function otp_verify_success(): void
    {
        Config::set('otp.show_code_on_screen', true);
        
        $request = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $code = $request->json('code');

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'destination' => 'test@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Verified']);
    }

    /** @test */
    public function otp_expired_code_fails_verification(): void
    {
        Config::set('otp.expiry_minutes', 0); // Expired immediately
        Config::set('otp.show_code_on_screen', true);
        
        $request = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $code = $request->json('code');

        // Manually expire the OTP in database
        OtpCode::where('destination_hash', hash('sha256', 'test@example.com'))
            ->where('purpose', 'register')
            ->update(['expires_at' => now()->subMinute()]);

        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'destination' => 'test@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid or expired code']);
    }

    /** @test */
    public function otp_wrong_code_attempts_limited(): void
    {
        Config::set('otp.max_attempts', 3);
        Config::set('otp.show_code_on_screen', true);
        
        $request = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $code = $request->json('code');

        // Try wrong code 3 times (max_attempts)
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/v1/auth/otp/verify', [
                'destination' => 'test@example.com',
                'code' => '000000', // Wrong code
                'purpose' => 'register',
            ]);
            
            if ($i < 3) {
                $response->assertStatus(422);
            } else {
                // On 3rd attempt (exceeding max), should be invalidated
                $response->assertStatus(422);
            }
        }

        // Correct code should now also fail (code invalidated after max attempts)
        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'destination' => 'test@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function otp_used_code_cannot_be_replayed(): void
    {
        Config::set('otp.show_code_on_screen', true);
        
        $request = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $code = $request->json('code');

        // First verification - should succeed
        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'destination' => 'test@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);
        $response->assertStatus(200);

        // Second verification with same code - should fail
        $response = $this->postJson('/api/v1/auth/otp/verify', [
            'destination' => 'test@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);
        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid or expired code']);
    }

    /** @test */
    public function otp_resend_cooldown_enforced(): void
    {
        Config::set('otp.resend_cooldown', 60);
        Config::set('otp.show_code_on_screen', true);
        
        // First request
        $response1 = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $response1->assertStatus(200);

        // Immediate second request - should be rate limited
        $response2 = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $response2->assertStatus(429); // Too Many Requests
    }

    /** @test */
    public function otp_per_destination_limit_enforced(): void
    {
        Config::set('otp.per_destination_limit', 3);
        Config::set('otp.resend_cooldown', 0); // Disable cooldown for this test
        Config::set('otp.show_code_on_screen', true);
        
        // Make 3 requests (limit)
        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/api/v1/auth/otp/request', [
                'channel' => 'email',
                'destination' => 'test@example.com',
                'purpose' => 'register',
            ]);
            $response->assertStatus(200);
        }

        // 4th request should fail
        $response = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $response->assertStatus(429);
    }

    /** @test */
    public function otp_per_ip_limit_enforced(): void
    {
        Config::set('otp.per_ip_limit', 5);
        Config::set('otp.resend_cooldown', 0);
        Config::set('otp.per_destination_limit', 100); // High limit to isolate IP test
        Config::set('otp.show_code_on_screen', true);
        
        // Make 5 requests from same IP (default test IP)
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/auth/otp/request', [
                'channel' => 'email',
                'destination' => "test{$i}@example.com",
                'purpose' => 'register',
            ]);
            $response->assertStatus(200);
        }

        // 6th request from same IP should fail
        $response = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test6@example.com',
            'purpose' => 'register',
        ]);
        $response->assertStatus(429);
    }

    /** @test */
    public function otp_no_plaintext_stored_in_database(): void
    {
        Config::set('otp.show_code_on_screen', true);
        
        $request = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $code = $request->json('code');

        $otpRecord = OtpCode::where('purpose', 'register')->first();
        
        $this->assertNotNull($otpRecord);
        $this->assertNotEquals($code, $otpRecord->code_hash); // Plaintext not stored
        $this->assertEquals(hash('sha256', 'test@example.com'), $otpRecord->destination_hash); // Destination hashed
        
        // Verify code_hash is HMAC, not plaintext
        $this->assertNotEquals($code, $otpRecord->code_hash);
        $this->assertEquals(64, strlen($otpRecord->code_hash)); // SHA256 hex length
    }

    /** @test */
    public function otp_role_escalation_attempt_at_registration_fails(): void
    {
        Config::set('otp.show_code_on_screen', true);
        
        // Request OTP for registration
        $request = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'test@example.com',
            'purpose' => 'register',
        ]);
        $code = $request->json('code');

        // Verify OTP
        $this->postJson('/api/v1/auth/otp/verify', [
            'destination' => 'test@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);

        // Attempt to register with role escalation (should be ignored/forced to customer)
        $response = $this->postJson('/api/v1/auth/register/complete', [
            'contact' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'superadmin', // Attempt to escalate
        ]);

        $response->assertStatus(200);
        $user = $response->json('user');
        $this->assertEquals('customer', $user['role']); // Should be forced to customer
    }

    /** @test */
    public function otp_generic_response_for_unknown_account_on_login(): void
    {
        Config::set('otp.show_code_on_screen', true);
        
        // Request OTP for non-existent account
        $response = $this->postJson('/api/v1/auth/login/otp/request', [
            'contact' => 'nonexistent@example.com',
        ]);
        
        // Should return generic success (not reveal if account exists)
        $response->assertStatus(200)
            ->assertJson(['message' => 'Code sent']);
    }

    /** @test */
    public function otp_generic_response_for_known_account_on_login(): void
    {
        Config::set('otp.show_code_on_screen', true);
        
        // Create a user
        $user = \App\Models\User::factory()->create([
            'email' => 'existing@example.com',
            'role' => 'customer',
        ]);
        $user->assignRole('customer');

        // Request OTP for existing account
        $response = $this->postJson('/api/v1/auth/login/otp/request', [
            'contact' => 'existing@example.com',
        ]);
        
        // Should return generic success (not reveal if account exists)
        $response->assertStatus(200)
            ->assertJson(['message' => 'Code sent']);
    }

    /** @test */
    public function otp_web_login_flow(): void
    {
        Config::set('otp.show_code_on_screen', true);
        
        // Create user
        $user = \App\Models\User::factory()->create([
            'email' => 'webtest@example.com',
            'role' => 'customer',
        ]);
        $user->assignRole('customer');

        // Step 1: Request OTP via web
        $response = $this->post('/login/otp/request', [
            'contact' => 'webtest@example.com',
        ]);
        $response->assertRedirect('/login');
        $response->assertSessionHas('status', 'otp_sent');
        $response->assertSessionHas('contact', 'webtest@example.com');

        // Get the code via API (clear existing codes first to avoid cooldown)
        \App\Models\OtpCode::where('destination_hash', hash('sha256', 'webtest@example.com'))->delete();
        $apiResponse = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'webtest@example.com',
            'purpose' => 'login',
        ]);
        $code = $apiResponse->json('code');

        // Step 2: Verify OTP via web
        $response = $this->post('/login/otp/verify', [
            'contact' => 'webtest@example.com',
            'otp' => $code,
        ]);
        $response->assertRedirect('/dashboard');
    }

    /** @test */
    public function otp_web_register_flow(): void
    {
        Config::set('otp.show_code_on_screen', true);
        
        // Step 1: Request OTP via web
        $response = $this->post('/register/otp/request', [
            'contact' => 'newuser@example.com',
        ]);
        $response->assertRedirect('/register');
        $response->assertSessionHas('status', 'otp_sent');
        $response->assertSessionHas('contact', 'newuser@example.com');

        // Get the code via API (clear existing codes first to avoid cooldown)
        \App\Models\OtpCode::where('destination_hash', hash('sha256', 'newuser@example.com'))->delete();
        $apiResponse = $this->postJson('/api/v1/auth/otp/request', [
            'channel' => 'email',
            'destination' => 'newuser@example.com',
            'purpose' => 'register',
        ]);
        $code = $apiResponse->json('code');

        // Step 2: Verify OTP via web
        $response = $this->post('/register/otp/verify', [
            'contact' => 'newuser@example.com',
            'otp' => $code,
        ]);
        $response->assertRedirect('/register');
        $response->assertSessionHas('status', 'contact_verified');
        $response->assertSessionHas('verified_contact', 'newuser@example.com');

        // Step 3: Complete registration
        $response = $this->post('/register/complete', [
            'contact' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'name' => 'New User',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        $response->assertRedirect('/dashboard');
    }
}