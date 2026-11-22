<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('hotels.bookings.index');
echo 'Route: ' . $route->uri() . PHP_EOL;
echo 'Middleware: ' . implode(', ', $route->gatherMiddleware()) . PHP_EOL;
echo 'Action: ' . $route->getActionName() . PHP_EOL;