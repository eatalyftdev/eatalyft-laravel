<?php

use App\Console\Commands\SendDepartureReminders;
use App\Services\ErrorMonitoringService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\TrustCloudflareProxies::class);
        $middleware->alias(['firebase.id' => \App\Http\Middleware\VerifyFirebaseIdToken::class]);
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withCommands([
        TranslationsCheck::class,
        SendDepartureReminders::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception, ?string $context = null) {
            if (app()->bound(ErrorMonitoringService::class)) {
                app(ErrorMonitoringService::class)->report($exception, is_array($context) ? $context : []);
            }
        });
    })->create();
