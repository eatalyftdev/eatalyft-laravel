<?php

namespace Tests\Feature\RBAC;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_superadmin_can_access_all_routes(): void
    {
        $user = User::where('email', 'admin@eatalyft.com')->first();
        $this->actingAs($user);

        // Debug: check user
        $this->assertNotNull($user, 'User should exist');
        $this->assertEquals('superadmin', $user->role);
        $this->assertTrue($user->hasRole('superadmin'));

        $routes = [
            'dashboard' => ['get', '/dashboard'],

            'roles.index' => ['get', '/roles'],

            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'bus.trips.index' => ['get', '/bus/trips'],
            'bus.inventory.index' => ['get', '/bus/inventory'],


            'hotels.index' => ['get', '/hotels'],

            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'esim.plans.index' => ['get', '/esim/plans'],
            'esim.orders.index' => ['get', '/esim/orders'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'fx.margins.index' => ['get', '/fx/margins'],
            'fx.history.index' => ['get', '/fx/history'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($routes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            if ($response->getStatusCode() !== 200) {
                echo "FAILED: {$name} ({$uri}) - Status: " . $response->getStatusCode() . "\n";
                echo "Content: " . $response->getContent() . "\n";
            }
            $this->assertEquals(200, $response->getStatusCode(), "Superadmin failed to access {$name} ({$uri})");
        }
    }

    public function test_admin_can_access_most_routes_but_not_system_admin(): void
    {
        $user = User::where('email', 'admin2@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],

            'roles.index' => ['get', '/roles'],

            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'bus.trips.index' => ['get', '/bus/trips'],
            'bus.inventory.index' => ['get', '/bus/inventory'],


            'hotels.index' => ['get', '/hotels'],

            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'esim.plans.index' => ['get', '/esim/plans'],
            'esim.orders.index' => ['get', '/esim/orders'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'fx.margins.index' => ['get', '/fx/margins'],
            'fx.history.index' => ['get', '/fx/history'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Admin failed to access {$name} ({$uri})");
        }

        // Admin should NOT have system.admin permission
        $this->assertFalse($user->can('system.admin'));
    }

    public function test_support_agent_access(): void
    {
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        $user = User::where('email', 'support@eatalyft.com')->first();
        
        // Manually set up authentication
        $this->be($user, 'web');
        
        // Debug
        echo "User: " . $user->name . PHP_EOL;
        echo "Email verified: " . ($user->email_verified_at ? 'yes' : 'no') . PHP_EOL;
        echo "Can hotels.bookings.read: " . ($user->can('hotels.bookings.read') ? 'yes' : 'no') . PHP_EOL;
        echo "Has permission via hasPermissionTo: " . ($user->hasPermissionTo('hotels.bookings.read') ? 'yes' : 'no') . PHP_EOL;
        echo "Roles: " . implode(', ', $user->getRoleNames()->toArray()) . PHP_EOL;
        echo "Direct permissions: " . implode(', ', $user->getDirectPermissions()->pluck('name')->toArray()) . PHP_EOL;
        echo "All permissions: " . implode(', ', $user->getAllPermissions()->pluck('name')->toArray()) . PHP_EOL;
        
        // Test the gate directly
        $gate = app(\Illuminate\Contracts\Auth\Access\Gate::class);
        echo "Gate allows: " . ($gate->allows('hotels.bookings.read', $user) ? 'yes' : 'no') . PHP_EOL;
        echo "Gate denies: " . ($gate->denies('hotels.bookings.read', $user) ? 'yes' : 'no') . PHP_EOL;
        
        // Test the middleware directly
        $request = \Illuminate\Http\Request::create('/hotels/bookings', 'GET');
        $request->setUserResolver(fn () => $user);
        
        $middleware = new \Illuminate\Auth\Middleware\Authorize($gate);
        $next = fn ($req) => response('OK', 200);
        $response = $middleware->handle($request, $next, 'hotels.bookings.read');
        echo "Middleware response: " . $response->getStatusCode() . PHP_EOL;

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],


            'notifications.index' => ['get', '/notifications'],
            'audit.index' => ['get', '/audit'],


            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];
        
        // Test a single route with debug
        $response = $this->get('/hotels/bookings');
        echo "Response status: " . $response->getStatusCode() . PHP_EOL;
        echo "Response user: " . ($response->baseResponse->getOriginalContent() ? 'has content' : 'no content') . PHP_EOL;
        // Check session
        echo "Session user: " . (session('login_web_' . sha1('web')) ?? 'none') . PHP_EOL;

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            if ($response->getStatusCode() !== 200) {
                echo "FAILED: {$name} ({$uri}) - Status: " . $response->getStatusCode() . "\n";
                echo "Content: " . $response->getContent() . "\n";
            }
            $this->assertEquals(200, $response->getStatusCode(), "Support agent failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [
            'roles.index' => ['get', '/roles'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'bus.trips.index' => ['get', '/bus/trips'],
            'bus.inventory.index' => ['get', '/bus/inventory'],

            'hotels.index' => ['get', '/hotels'],
            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Support agent should be denied {$name} ({$uri})");
        }
    }

    public function test_kyc_reviewer_access(): void
    {
        $user = User::where('email', 'kyc@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],


            'notifications.index' => ['get', '/notifications'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "KYC reviewer failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [
            'roles.index' => ['get', '/roles'],
            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'hotels.index' => ['get', '/hotels'],
            'listings.index' => ['get', '/listings'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "KYC reviewer should be denied {$name} ({$uri})");
        }
    }

    public function test_listing_moderator_access(): void
    {
        $user = User::where('email', 'moderator@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],
            'listings.index' => ['get', '/listings'],


            'notifications.index' => ['get', '/notifications'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Listing moderator failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [
            'roles.index' => ['get', '/roles'],
            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'hotels.index' => ['get', '/hotels'],
            'esim.providers.index' => ['get', '/esim/providers'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Listing moderator should be denied {$name} ({$uri})");
        }
    }

    public function test_agency_manager_access(): void
    {
        $user = User::where('email', 'agency@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'bus.trips.index' => ['get', '/bus/trips'],
            'bus.inventory.index' => ['get', '/bus/inventory'],


            'hotels.index' => ['get', '/hotels'],
            'hotels.rooms.index' => ['get', '/hotels/rooms'],
            'hotels.rates.index' => ['get', '/hotels/rates'],
            'hotels.availability.index' => ['get', '/hotels/availability'],



            'notifications.index' => ['get', '/notifications'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Agency manager failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [
            'roles.index' => ['get', '/roles'],
            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Agency manager should be denied {$name} ({$uri})");
        }
    }

    public function test_branch_manager_access(): void
    {
        $user = User::where('email', 'branch@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'bus.trips.index' => ['get', '/bus/trips'],
            'bus.inventory.index' => ['get', '/bus/inventory'],


            'hotels.index' => ['get', '/hotels'],
            'hotels.rooms.index' => ['get', '/hotels/rooms'],
            'hotels.rates.index' => ['get', '/hotels/rates'],
            'hotels.availability.index' => ['get', '/hotels/availability'],



            'notifications.index' => ['get', '/notifications'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Branch manager failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [
            'roles.index' => ['get', '/roles'],
            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Branch manager should be denied {$name} ({$uri})");
        }
    }

    public function test_ticket_agent_access(): void
    {
        $user = User::where('email', 'ticket@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'bus.trips.index' => ['get', '/bus/trips'],
            'bus.inventory.index' => ['get', '/bus/inventory'],




            'notifications.index' => ['get', '/notifications'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Ticket agent failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [
            'roles.index' => ['get', '/roles'],
            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'hotels.index' => ['get', '/hotels'],
            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Ticket agent should be denied {$name} ({$uri})");
        }
    }

    public function test_ticket_scanner_access(): void
    {
        $user = User::where('email', 'scanner@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],
            'bus.trips.index' => ['get', '/bus/trips'],
            'currencies.index' => ['get', '/currencies'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Ticket scanner failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [
            'roles.index' => ['get', '/roles'],
            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'bus.inventory.index' => ['get', '/bus/inventory'],


            'hotels.index' => ['get', '/hotels'],
            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Ticket scanner should be denied {$name} ({$uri})");
        }
    }

    public function test_hotel_manager_access(): void
    {
        $user = User::where('email', 'hotel@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],
            'hotels.index' => ['get', '/hotels'],
            'hotels.rooms.index' => ['get', '/hotels/rooms'],
            'hotels.rates.index' => ['get', '/hotels/rates'],
            'hotels.availability.index' => ['get', '/hotels/availability'],



            'notifications.index' => ['get', '/notifications'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Hotel manager failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [
            'roles.index' => ['get', '/roles'],
            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'bus.trips.index' => ['get', '/bus/trips'],
            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Hotel manager should be denied {$name} ({$uri})");
        }
    }

    public function test_customer_access(): void
    {
        $user = User::where('email', 'customer@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
            'notifications.index' => ['get', '/notifications'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Customer failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [

            'roles.index' => ['get', '/roles'],

            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],


            'hotels.index' => ['get', '/hotels'],

            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'esim.orders.index' => ['get', '/esim/orders'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Customer should be denied {$name} ({$uri})");
        }
    }

    public function test_creator_access(): void
    {
        $user = User::where('email', 'creator@eatalyft.com')->first();
        $this->actingAs($user);

        $allowedRoutes = [
            'dashboard' => ['get', '/dashboard'],
            'profile.show' => ['get', '/profile'],
            'settings.index' => ['get', '/settings'],
            'notifications.index' => ['get', '/notifications'],
            'currencies.index' => ['get', '/currencies'],
            'fx.rates.index' => ['get', '/fx/rates'],
        ];

        foreach ($allowedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(200, $response->getStatusCode(), "Creator failed to access {$name} ({$uri})");
        }

        $deniedRoutes = [

            'roles.index' => ['get', '/roles'],

            'audit.index' => ['get', '/audit'],
            'system.settings' => ['get', '/system/settings'],
            'agencies.index' => ['get', '/admin/agencies'],
            'bus.routes.index' => ['get', '/bus/routes'],
            'hotels.index' => ['get', '/hotels'],
            'listings.index' => ['get', '/listings'],
            'esim.providers.index' => ['get', '/esim/providers'],
            'boxo.miniapps.index' => ['get', '/boxo/miniapps'],
            'integrations.index' => ['get', '/integrations'],
        ];

        foreach ($deniedRoutes as $name => [$method, $uri]) {
            $response = $this->{$method}($uri);
            $this->assertEquals(403, $response->getStatusCode(), "Creator should be denied {$name} ({$uri})");
        }
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $routes = [
            '/dashboard',
            '/users',
            '/roles',
            '/kyc',
            '/audit',
            '/system/settings',
            '/admin/agencies',
            '/bus/routes',
            '/bus/trips',
            '/bus/inventory',
            '/hotels',
            '/listings',
            '/esim/providers',
            '/boxo/miniapps',
            '/integrations',
            '/currencies',
            '/fx/rates',
            '/profile',
            '/settings',
        ];

        foreach ($routes as $uri) {
            $response = $this->get($uri);
            $this->assertEquals(302, $response->getStatusCode());
            $this->assertStringContainsString('/login', $response->getTargetUrl());
        }
    }
}
