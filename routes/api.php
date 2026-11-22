<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\FirebaseAuthController;

Route::prefix('v1/auth')->group(function () {
    Route::post('otp/request', [AuthController::class, 'requestOtp']);
    Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
    Route::post('login/otp/request', [AuthController::class, 'loginOtpRequest']);
    Route::post('login/otp', [AuthController::class, 'loginOtp']);
    Route::post('register/complete', [AuthController::class, 'completeRegister']);

    // Firebase mobile authentication
    Route::post('firebase/login', [FirebaseAuthController::class, 'login'])->middleware(\App\Http\Middleware\VerifyFirebaseIdToken::class);
    Route::post('firebase/link', [FirebaseAuthController::class, 'link'])->middleware(\App\Http\Middleware\VerifyFirebaseIdToken::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });
});

Route::get('/health', HealthController::class);

// Public bus discovery; all inventory mutations are handled by Laravel/MySQL.
Route::prefix('v1/bus')->group(function () {
    Route::get('/trips', [\App\Modules\Bus\Http\Controllers\BusDiscoveryController::class, 'index']);
    Route::get('/trips/{trip}', [\App\Modules\Bus\Http\Controllers\BusDiscoveryController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/bookings/holds', [\App\Modules\Bus\Http\Controllers\BusBookingApiController::class, 'store']);
        Route::get('/bookings/mine', [\App\Modules\Bus\Http\Controllers\BusBookingApiController::class, 'mine']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('currency')->group(function () {
        Route::post('/switch', [CurrencyController::class, 'switch']);
        Route::get('/current', [CurrencyController::class, 'current']);
    });

    Route::prefix('language')->group(function () {
        Route::post('/switch', [LanguageController::class, 'switch']);
        Route::get('/current', [LanguageController::class, 'current']);
    });
});
