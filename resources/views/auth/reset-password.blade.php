<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Eatalyft') }} - {{ __('Reset Password') }}</title>
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
                            <div class="card-body p-5">
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

                                <h4 class="text-center mb-3">{{ __('Reset Password') }}</h4>
                                <p class="text-center text-muted mb-4">{{ __('Enter your new password') }}</p>

                                @if ($errors->any())
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('password.store') }}">
                                    @csrf
                                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                    <div class="mb-3">
                                        <label for="email" class="form-label">{{ __('Email') }}</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ $request->input('email') }}" required autocomplete="email">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label">{{ __('Password') }}</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-4">
                                        <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                                        @error('password_confirmation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-primary">{{ __('Reset Password') }}</button>
                                    </div>
                                </form>

                                <p class="text-center mb-0">
                                    {{ __('Remember your password?') }}
                                    <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">{{ __('Sign in') }}</a>
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