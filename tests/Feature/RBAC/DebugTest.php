<?php

namespace Tests\Feature\RBAC;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_debug_dashboard(): void
    {
        $user = User::where('email', 'admin@eatalyft.com')->first();
        $this->assertNotNull($user, 'User should exist');
        $this->assertEquals('superadmin', $user->role);
        $this->assertTrue($user->hasRole('superadmin'));

        $this->actingAs($user);
        
        $response = $this->get('/dashboard');
        
        // Print the response content to see the error
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Content: " . $response->getContent() . "\n";
    }
}