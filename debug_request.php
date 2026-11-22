<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

Config::set('otp.show_code_on_screen', true);

echo "Config value: " . (config('otp.show_code_on_screen') ? 'true' : 'false') . "\n";

$response = $app->handle(\Illuminate\Http\Request::create('/api/v1/auth/otp/request', 'POST', [
    'channel' => 'email',
    'destination' => 'test@example.com',
    'purpose' => 'register',
], [], [], ['CONTENT_TYPE' => 'application/json', 'ACCEPT' => 'application/json']));

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";