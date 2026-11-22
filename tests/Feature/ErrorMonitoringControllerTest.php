<?php

namespace Tests\Feature;

use App\Models\ErrorIncident;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorMonitoringControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_access_error_dashboard(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'customer',
            'status' => 'active',
        ]);
        $customer->assignRole('customer');

        $response = $this->actingAs($customer)->get(route('admin.monitoring.errors.index'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_provider_cannot_access_error_dashboard(): void
    {
        $provider = User::factory()->create([
            'email' => 'provider@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'provider',
            'status' => 'active',
        ]);
        $provider->assignRole('provider');

        $response = $this->actingAs($provider)->get(route('admin.monitoring.errors.index'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_error_dashboard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.monitoring.errors.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.monitoring.errors.index');
    }

    public function test_error_dashboard_shows_incidents(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        ErrorIncident::factory()->count(3)->create([
            'exception_class' => 'App\\Exceptions\\TestException',
            'message' => 'Test error message',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.monitoring.errors.index'));

        $response->assertStatus(200);
        $response->assertViewHas('incidents');
        $response->assertSee('App\\Exceptions\\TestException');
    }

    public function test_admin_can_view_incident_details(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $incident = ErrorIncident::factory()->create([
            'exception_class' => 'Test\\Exception',
            'message' => 'Test message',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.monitoring.errors.show', $incident));

        $response->assertStatus(200);
        $response->assertViewIs('admin.monitoring.errors.show');
        $response->assertSee('Test\\Exception');
        $response->assertSee('Test message');
    }

    public function test_customer_cannot_access_incident_details(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'customer',
            'status' => 'active',
        ]);
        $customer->assignRole('customer');

        $incident = ErrorIncident::factory()->create();

        $response = $this->actingAs($customer)->get(route('admin.monitoring.errors.show', $incident));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_resolve_incident(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $incident = ErrorIncident::factory()->create(['status' => 'open']);

        $response = $this->actingAs($admin)->post(route('admin.monitoring.errors.resolve', $incident));

        $response->assertRedirect(route('admin.monitoring.errors.show', $incident));
        $this->assertTrue($incident->fresh()->isResolved());
    }

    public function test_customer_cannot_resolve_incident(): void
    {
        $customer = User::factory()->create([
            'email' => 'customer@test.com',
            'password' => password_hash('secret123', PASSWORD_BCRYPT),
            'role' => 'customer',
            'status' => 'active',
        ]);
        $customer->assignRole('customer');

        $incident = ErrorIncident::factory()->create();

        $response = $this->actingAs($customer)->post(route('admin.monitoring.errors.resolve', $incident));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $this->assertFalse($incident->fresh()->isResolved());
    }
}
