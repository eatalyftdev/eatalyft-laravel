<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Eatalyft') }} - {{ __('Verify Email') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="authentication">
    <div class="authentication-wrapper authentication-1">
        <div class="authentication-inner py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5 col-xl-4">
                        <div class="card">
                            <div class="card-body p-5 text-center">
                                <div class="text-center mb-4">
                                    <a href="{{ route('dashboard') }}" class="app-brand-link d-inline-flex align-items-center">
                                        <span class="app-brand-logo demo">
                                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            </svg>
                                        </span>
                                        <span class="app-brand-text demo menu-text fw-bold ms-2 fs-3">Eatalyft</span>
                                    </a>
                                </div>

                                <h4 class="mb-3">{{ __('Verify Your Email') }}</h4>
                                <p class="text-muted mb-4">{{ __('Thanks for registering! Please verify your email address.') }}</p>

                                @if (session('status') === 'verification-link-sent')
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ __('A new verification link has been sent to your email.') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

                                @if (auth()->user()->hasVerifiedEmail())
                                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                                        {{ __('Your email is already verified.') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @else
                                    <p class="mb-3">{{ __('We sent a verification link to') }} <strong>{{ auth()->user()->email }}</strong></p>

                                    <form method="POST" action="{{ route('verification.send') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">{{ __('Resend Verification Link') }}</button>
                                    </form>
                                @endif

                                <p class="mt-4 mb-0 text-muted small">
                                    <a href="{{ route('logout') }}" class="text-decoration-none">{{ __('Logout') }}</a>
                                </p>
                            </div>
                        </div>

                        <div class="text-center mt-3 text-muted small">
                            &copy; {{ date('Y') }} {{ config('app.name', 'Eatalyft') }}. {{ __('All rights reserved.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>