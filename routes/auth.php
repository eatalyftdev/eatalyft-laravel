<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login/otp/request', [AuthController::class, 'loginOtpRequest'])->name('login.otp.request');
    Route::post('login/otp/verify', [AuthController::class, 'loginOtpVerify'])->name('login.otp.verify');

    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::get('register/customer', [AuthController::class, 'showRegisterCustomer'])->name('register.customer');
    Route::get('register/provider', [AuthController::class, 'showRegisterProvider'])->name('register.provider');
    Route::get('register/agency', [AuthController::class, 'showRegisterAgency'])->name('register.agency');
    Route::post('register/otp/request', [AuthController::class, 'registerOtpRequest'])->name('register.otp.request');
    Route::post('register/otp/verify', [AuthController::class, 'registerOtpVerify'])->name('register.otp.verify');
    Route::post('register/complete', [AuthController::class, 'registerComplete'])->name('register.complete');
    Route::post('register/provider/submit', [App\Http\Controllers\Auth\ProviderRegistrationController::class, 'submit'])->name('register.provider.submit');
    Route::post('register/agency/submit', [App\Http\Controllers\Auth\AgencyRegistrationController::class, 'submit'])->name('register.agency.submit');

    Route::get('forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');

    Route::get('reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.store');
});

Route::prefix('admin')->middleware('guest')->group(function () {
    Route::get('login', [App\Http\Controllers\Auth\AdminLoginController::class, 'showLogin'])->name('admin.login');
    Route::post('login', [App\Http\Controllers\Auth\AdminLoginController::class, 'login'])->name('admin.login.post');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [AuthController::class, 'sendVerificationEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [AuthController::class, 'showConfirmPassword'])->name('password.confirm');
    Route::post('confirm-password', [AuthController::class, 'confirmPassword']);

    Route::put('password', [AuthController::class, 'updatePassword'])->name('password.update');

    Route::post('logout', [AuthController::class, 'logoutWeb'])->name('logout');
});