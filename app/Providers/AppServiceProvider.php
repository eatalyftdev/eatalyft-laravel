<?php

namespace App\Providers;

use App\Channels\TelegramChannel;
use App\Services\ErrorMonitoringService;
use App\Services\Notification\BookingNotificationService;
use App\Services\Payment\NotImplementedPaymentGateway;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ErrorMonitoringService::class, function ($app) {
            return new ErrorMonitoringService($app['config']['error_monitoring']);
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService(new NotImplementedPaymentGateway());
        });

        $this->app->singleton(BookingNotificationService::class, function ($app) {
            return new BookingNotificationService();
        });
    }

    public function boot(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Notification::extend('telegram', function () {
            return new TelegramChannel();
        });
    }
}