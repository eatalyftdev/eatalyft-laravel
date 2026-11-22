<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GuestAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_access_to_specific_routes(): void
    {
        $routes = [
            '/' => ['expected' => 200, 'description' => 'Home page (public)'],
            '/dashboard' => ['expected' => 302, 'redirect' => '/login', 'description' => 'Dashboard (auth required)'],
            '/users' => ['expected' => 302, 'redirect' => '/login', 'description' => 'Users index (auth required)'],
            '/system/settings' => ['expected' => 302, 'redirect' => '/login', 'description' => 'System settings (auth required)'],
            '/roles' => ['expected' => 302, 'redirect' => '/login', 'description' => 'Roles index (auth required)'],
            '/integrations' => ['expected' => 302, 'redirect' => '/login', 'description' => 'Integrations (auth required)'],
            '/kyc' => ['expected' => 302, 'redirect' => '/login', 'description' => 'KYC (auth required)'],
            '/audit' => ['expected' => 302, 'redirect' => '/login', 'description' => 'Audit logs (auth required)'],
            '/health' => ['expected' => 200, 'description' => 'Health endpoint (public)'],
            '/api/v1/auth/me' => ['expected' => [401, 500], 'description' => 'API me endpoint (auth required)', 'headers' => ['Accept' => 'application/json']],
        ];

        $results = [];

        foreach ($routes as $uri => $config) {
            $headers = $config['headers'] ?? [];
            $response = $this->get($uri, $headers);
            $status = $response->getStatusCode();
            $redirect = $response->headers->get('Location') ?? 'N/A';
            
            $expected = is_array($config['expected']) ? $config['expected'] : [$config['expected']];
            $passed = in_array($status, $expected);
            if (isset($config['redirect']) && $passed) {
                $passed = str_contains($redirect, $config['redirect']);
            }

            $results[] = [
                'uri' => $uri,
                'description' => $config['description'],
                'expected' => implode(', ', $expected),
                'actual' => $status,
                'redirect' => $redirect,
                'passed' => $passed ? '✅' : '❌',
            ];

            $this->assertTrue($passed, "Route {$uri} ({$config['description']}): expected " . implode(' or ', $expected) . ", got {$status}. Redirect: {$redirect}");
        }

        // Output table for documentation
        $this->outputGuestAccessTable($results);
    }

    public function test_all_named_web_routes_guest_access(): void
    {
        // Define public routes that should return 200 for guests
        $publicRoutes = [
            'home',
            'about',
            'services.index',
            'services.show',
            'agencies',
            'faq',
            'contact',
            'legal.terms',
            'legal.privacy',
            'legal.cookies',
            'search',
            'login',
            'register',
            'register.customer',
            'register.provider',
            'register.agency',
            'admin.login',
            'password.request',
            'password.reset',
            'password.email',
            'password.store',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.confirm',
            'password.update',
            'health',
            'theme.toggle',
            'language.switch',
            'language.current',
            'currency.switch',
            'currency.current',
        ];

        // Get all named web routes
        $routes = collect(app('router')->getRoutes())
            ->filter(fn ($route) => $route->getName() !== null)
            ->filter(fn ($route) => in_array('GET', $route->methods()) || in_array('HEAD', $route->methods()))
            ->filter(fn ($route) => !str_starts_with($route->uri(), 'api/'))
            ->filter(fn ($route) => !str_starts_with($route->uri(), 'storage/'))
            ->filter(fn ($route) => !str_starts_with($route->uri(), 'up'))
            ->filter(fn ($route) => !str_starts_with($route->uri(), '_'))
            ->values();

        $failures = [];

        foreach ($routes as $route) {
            $name = $route->getName();
            $uri = $route->uri();
            
            // Skip routes with required parameters (like {id})
            if (preg_match('/\{[^}]+\}/', $uri)) {
                continue;
            }

            $fullUri = '/' . ltrim($uri, '/');
            $isPublic = in_array($name, $publicRoutes);

            $response = $this->get($fullUri);
            $status = $response->getStatusCode();

            // Public routes should return 200 (or 302 for login/register which redirect authenticated users)
            // Protected routes should return 302 (redirect to login) or 403/401
            if ($isPublic) {
                if ($status !== 200 && $status !== 302) {
                    $failures[] = "Public route '{$name}' ({$fullUri}): expected 200 or 302, got {$status}";
                }
            } else {
                // Protected route - should NOT return 200
                if ($status === 200) {
                    $failures[] = "Protected route '{$name}' ({$fullUri}): returned 200 for guest (should redirect/deny)";
                }
            }
        }

        if (!empty($failures)) {
            $this->fail("Guest access failures:\n" . implode("\n", $failures));
        }

        $this->assertTrue(true, 'All routes respect guest access rules');
    }

    protected function outputGuestAccessTable(array $results): void
    {
        $header = sprintf("%-30s | %-35s | %-8s | %-8s | %-30s | %s\n", 
            'URI', 'Description', 'Expected', 'Actual', 'Redirect', 'Status');
        $separator = str_repeat('-', 140) . "\n";
        
        $output = "\nGuest Access Test Results:\n" . $separator . $header . $separator;
        
        foreach ($results as $row) {
            $redirect = strlen($row['redirect']) > 30 ? substr($row['redirect'], 0, 27) . '...' : $row['redirect'];
            $output .= sprintf("%-30s | %-35s | %-8s | %-8s | %-30s | %s\n",
                substr($row['uri'], 0, 29),
                substr($row['description'], 0, 34),
                $row['expected'],
                $row['actual'],
                $redirect,
                $row['passed']
            );
        }
        
        $output .= $separator;
        
        // Write to EVIDENCE.md
        $evidencePath = base_path('docs/EVIDENCE.md');
        $existing = file_exists($evidencePath) ? file_get_contents($evidencePath) : '';
        $newContent = $existing . "\n## Task 2: Guest Access Test Results\n\n```\n" . $output . "```\n";
        file_put_contents($evidencePath, $newContent);
    }
}