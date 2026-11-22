<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Eatalyft') }} - {{ __('Admin Login') }}</title>
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
                                    <a href="{{ route('home') }}" class="app-brand-link d-inline-flex align-items-center">
                                        <span class="app-brand-text demo menu-text fw-bold ms-2 fs-3">EataLyft</span>
                                    </a>
                                </div>

                                <h4 class="text-center mb-3">{{ __('Admin Login') }}</h4>
                                <p class="text-center text-muted mb-4">{{ __('Administrator access only') }}</p>

                                <div class="alert alert-warning mb-4">
                                    <i class="bi bi-shield-exclamation me-2"></i>
                                    {{ __('This area is restricted to administrators. Customer, provider, and agency accounts cannot log in here.') }}
                                </div>

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

                                <form method="POST" action="{{ route('admin.login.post') }}">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="email" class="form-label">{{ __('Email Address') }}</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required autofocus>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label">{{ __('Password') }}</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-primary">{{ __('Sign In as Administrator') }}</button>
                                    </div>
                                </form>

                                <p class="text-center mt-4 mb-0">
                                    {{ __('Not an administrator?') }}
                                    <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">{{ __('Customer login') }}</a>
                                    <a href="{{ route('register') }}" class="text-decoration-none fw-semibold ms-2">{{ __('or create account') }}</a>
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
