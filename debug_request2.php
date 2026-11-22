<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

// Simulate the test
$app['config']->set('otp.show_code_on_screen', true);
$app['config']->set('otp.max_attempts', 3);

echo "Config show_code_on_screen: " . ($app['config']->get('otp.show_code_on_screen') ? 'true' : 'false') . "\n";
echo "Config max_attempts: " . $app['config']->get('otp.max_attempts') . "\n";

$response = $app->handle(\Illuminate\Http\Request::create('/api/v1/auth/otp/request', 'POST', [
    'channel' => 'email',
    'destination' => 'test@example.com',
    'purpose' => 'register',
], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'ACCEPT' => 'application/json',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
]));

echo "Status: " . $response->getStatusCode() . "\n";
echo "Headers: " . json_encode($response->headers->all()) . "\n";
echo "Content: " . $response->getContent() . "\n";