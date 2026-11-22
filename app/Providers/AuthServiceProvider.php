<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\AgencyPolicy;
use App\Policies\BranchPolicy;
use App\Policies\BusBookingPolicy;
use App\Policies\BusTicketPolicy;
use App\Policies\HotelBookingPolicy;
use App\Policies\HotelPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('view-agency', [AgencyPolicy::class, 'view']);
        Gate::define('update-agency', [AgencyPolicy::class, 'update']);
        Gate::define('delete-agency', [AgencyPolicy::class, 'delete']);

        Gate::define('view-branch', [BranchPolicy::class, 'view']);
        Gate::define('update-branch', [BranchPolicy::class, 'update']);
        Gate::define('delete-branch', [BranchPolicy::class, 'delete']);

        Gate::define('view-bus-ticket', [BusTicketPolicy::class, 'view']);
        Gate::define('update-bus-ticket', [BusTicketPolicy::class, 'update']);
        Gate::define('cancel-bus-ticket', [BusTicketPolicy::class, 'cancel']);
        Gate::define('scan-bus-ticket', [BusTicketPolicy::class, 'scan']);

        Gate::define('view-bus-booking', [BusBookingPolicy::class, 'view']);
        Gate::define('update-bus-booking', [BusBookingPolicy::class, 'update']);
        Gate::define('confirm-bus-booking', [BusBookingPolicy::class, 'confirm']);
        Gate::define('cancel-bus-booking', [BusBookingPolicy::class, 'cancel']);

        Gate::define('view-hotel', [HotelPolicy::class, 'view']);
        Gate::define('update-hotel', [HotelPolicy::class, 'update']);
        Gate::define('delete-hotel', [HotelPolicy::class, 'delete']);

        Gate::define('view-hotel-booking', [HotelBookingPolicy::class, 'view']);
        Gate::define('update-hotel-booking', [HotelBookingPolicy::class, 'update']);
        Gate::define('confirm-hotel-booking', [HotelBookingPolicy::class, 'confirm']);
        Gate::define('cancel-hotel-booking', [HotelBookingPolicy::class, 'cancel']);
    }
}