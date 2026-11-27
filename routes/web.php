<?php

use App\Http\Controllers\Admin\AgencyController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BoxoController;
use App\Http\Controllers\Admin\BusBookingController;
use App\Http\Controllers\Admin\BusController;
use App\Http\Controllers\Admin\CurrencyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EsimController;
use App\Http\Controllers\Admin\FxController;
use App\Http\Controllers\Admin\HotelBookingController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\ErrorMonitoringController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\KycController;
use App\Http\Controllers\Admin\ListingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CurrencyController as FrontendCurrencyController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/legacy-home', [PublicController::class, 'legacyHome'])->name('legacy.home');
Route::get('/search', [PublicController::class, 'search'])->name('search');

Route::get('/bus/search', [PublicController::class, 'busSearch'])->name('bus.search');
Route::get('/bus/trips/{trip}', [PublicController::class, 'busTripShow'])->name('bus.trip.show');

// Customer bus booking flows (auth required)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/bus/seats/select', [App\Http\Controllers\Customer\BusBookingController::class, 'selectSeats'])->name('bus.seats.select');
    Route::get('/bus/bookings/create', [App\Http\Controllers\Customer\BusBookingController::class, 'create'])->name('bus.bookings.create');
    Route::post('/bus/bookings', [App\Http\Controllers\Customer\BusBookingController::class, 'store'])->name('bus.bookings.store');
    Route::get('/bus/bookings/{booking}', [App\Http\Controllers\Customer\BusBookingController::class, 'show'])->name('bus.bookings.show');
    Route::post('/bus/bookings/{booking}/payment', [App\Http\Controllers\Customer\PaymentController::class, 'pay'])->name('bus.bookings.payment');
    Route::get('/bus/bookings/{booking}/payment', [App\Http\Controllers\Customer\PaymentController::class, 'show'])->name('bus.bookings.payment');
    Route::post('/bus/bookings/{booking}/cancel', [App\Http\Controllers\Customer\BusBookingController::class, 'cancel'])->name('bus.bookings.cancel');
    Route::get('/bus/tickets/{ticket}', [App\Http\Controllers\Customer\BusTicketController::class, 'show'])->name('bus.tickets.show');
});

Route::get('/services', [PublicController::class, 'services'])->name('services.index');
Route::get('/services/{slug}', [PublicController::class, 'serviceShow'])->name('services.show');

Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/agencies', [PublicController::class, 'agencies'])->name('agencies');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/faq', [PublicController::class, 'faq'])->name('faq');

Route::prefix('legal')->group(function () {
    Route::get('/terms', [PublicController::class, 'terms'])->name('legal.terms');
    Route::get('/privacy', [PublicController::class, 'privacy'])->name('legal.privacy');
    Route::get('/cookies', [PublicController::class, 'cookies'])->name('legal.cookies');
});

Route::post('/theme', [ThemeController::class, 'toggle'])->name('theme.toggle');

Route::get('/health', HealthController::class)->name('health');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('currency')->group(function () {
        Route::post('/switch', [FrontendCurrencyController::class, 'switch'])->name('currency.switch');
        Route::get('/current', [FrontendCurrencyController::class, 'current'])->name('currency.current');
    });

    Route::prefix('language')->group(function () {
        Route::post('/switch', [LanguageController::class, 'switch'])->name('language.switch');
        Route::get('/current', [LanguageController::class, 'current'])->name('language.current');
    });

    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');

    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings.index');

    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password.update');

    // Admin routes (permission-gated via middleware)
    Route::middleware('can:users.read')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    });

    Route::middleware('can:roles.read')->group(function () {
        Route::resource('roles', RoleController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    });

    Route::middleware('can:kyc.read')->group(function () {
        Route::resource('kyc', KycController::class)->only(['index', 'show', 'edit', 'update']);
    });

    Route::middleware('can:audit.read')->group(function () {
        Route::resource('audit', AuditController::class)->only(['index', 'show']);
    });

    Route::middleware('can:system.settings.read')->group(function () {
        Route::get('/system/settings', [SettingsController::class, 'index'])->name('system.settings');
        Route::put('/system/settings', [SettingsController::class, 'updateSystem'])->name('system.settings.update');
    });

    Route::middleware('can:agencies.read')->group(function () {
        Route::resource('admin/agencies', AgencyController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::resource('admin/agencies.branches', AgencyController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('agencies.branches');
        Route::resource('admin/agencies.staff', AgencyController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('agencies.staff');
    });

    Route::middleware('can:bus.routes.read')->group(function () {
        Route::resource('bus/routes', BusController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('bus.routes');
    });

    Route::middleware('can:bus.trips.read')->group(function () {
        Route::resource('bus/trips', BusController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('bus.trips');
    });

    Route::middleware('can:bus.inventory.read')->group(function () {
        Route::resource('bus/inventory', BusController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('bus.inventory');
    });

    Route::middleware('can:bus.tickets.read')->group(function () {
        Route::resource('bus/tickets', BusController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('bus.tickets');
    });

    Route::middleware('can:bus.bookings.read')->group(function () {
        Route::resource('bus/bookings', BusBookingController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('bus.bookings');
    });

    Route::middleware('can:hotels.bookings.read')->group(function () {
        Route::resource('hotels/bookings', HotelBookingController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('hotels.bookings');
    });

    Route::middleware('can:hotels.read')->group(function () {
        Route::resource('hotels/rooms', HotelController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('hotels.rooms');
        Route::resource('hotels/rates', HotelController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('hotels.rates');
        Route::resource('hotels/availability', HotelController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('hotels.availability');
        Route::resource('hotels', HotelController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('hotels')->where(['hotel' => '[0-9]+']);
    });

    Route::middleware('can:listings.read')->group(function () {
        Route::resource('listings', ListingController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('listings');
    });

    Route::middleware('can:esim.read')->group(function () {
        Route::resource('esim/providers', EsimController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('esim.providers');
        Route::resource('esim/plans', EsimController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('esim.plans');
        Route::resource('esim/orders', EsimController::class)->only(['index', 'show'])->names('esim.orders');
    });

    Route::middleware('can:boxo.miniapps.read')->group(function () {
        Route::resource('boxo/miniapps', BoxoController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('boxo.miniapps');
    });

    Route::middleware('can:integrations.read')->group(function () {
        Route::resource('integrations', IntegrationController::class)->only(['index', 'show'])->names('integrations');
    });

    Route::middleware('can:currencies.read')->group(function () {
        Route::resource('currencies', CurrencyController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('currencies');
    });

    Route::middleware('can:fx.rates.read')->group(function () {
        Route::resource('fx/rates', FxController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('fx.rates');
        Route::resource('fx/margins', FxController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update'])->names('fx.margins');
        Route::resource('fx/history', FxController::class)->only(['index', 'show'])->names('fx.history');
    });

    Route::middleware('can:notifications.read')->group(function () {
        Route::resource('notifications', NotificationController::class)->only(['index', 'show'])->names('notifications');
    });

    // Error monitoring dashboard (admin only)
    // Agency bus operations (Agency managers only)
    Route::middleware(['auth', 'verified', 'can:agencies.read'])->group(function () {
        Route::get('/agency/bus/dashboard', [App\Http\Controllers\Agency\BusController::class, 'index'])->name('agency.bus.dashboard');
        Route::get('/agency/bus/trips/{trip}', [App\Http\Controllers\Agency\BusController::class, 'tripShow'])->name('agency.bus.trips.show');
        Route::post('/agency/bus/trips/{trip}/scan', [App\Http\Controllers\Agency\BusController::class, 'scanTicket'])->name('agency.bus.scan');
    });
});

require __DIR__.'/auth.php';