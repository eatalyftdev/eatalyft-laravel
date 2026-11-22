<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminLoginControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('admin_login:*');
    }

    public function test_admin_login_page_loads(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.admin-login');
    }

    public function test_admin_login_with_valid_credentials_succeeds(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $response = $this->post(route('admin.login.post'), [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_login_with_invalid_credentials_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $user->assignRole('admin');

        $response = $this->post(route('admin.login.post'), [
            'email' => 'admin@test.com',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_non_admin_user_cannot_access_admin_login(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@test.com',
            'role' => 'customer',
            'status' => 'active',
        ]);
        $customer->assignRole('customer');

        $response = $this->post(route('admin.login.post'), [
            'email' => 'customer@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_admin_cannot_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'inactive',
        ]);
        $admin->assignRole('admin');

        $response = $this->post(route('admin.login.post'), [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rate_limiting_blocks_after_five_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $user->assignRole('admin');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.post'), [
                'email' => 'admin@test.com',
                'password' => 'wrong_password',
            ]);
        }

        $response = $this->post(route('admin.login.post'), [
            'email' => 'admin@test.com',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts', $response->getSession()->get('errors')->first('email'));
    }

    public function test_rate_limiter_clears_after_successful_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->post(route('admin.login.post'), [
            'email' => 'admin@test.com',
            'password' => 'wrong_password',
        ]);
        $this->post(route('admin.login.post'), [
            'email' => 'admin@test.com',
            'password' => 'wrong_password',
        ]);

        $response = $this->post(route('admin.login.post'), [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_login_requires_email(): void
    {
        $response = $this->post(route('admin.login.post'), [
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_admin_login_requires_password(): void
    {
        $response = $this->post(route('admin.login.post'), [
            'email' => 'admin@test.com',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
