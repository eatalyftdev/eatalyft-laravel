<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Eatalyft') }} - {{ __('Register Transport Agency') }}</title>
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

                                <h4 class="text-center mb-3">{{ __('Register Transport Agency') }}</h4>
                                <p class="text-center text-muted mb-4">{{ __('Register your transport agency to manage routes, trips, and bookings.') }}</p>

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

                                <form method="POST" action="{{ route('register.agency.submit') }}">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="agency_name" class="form-label">{{ __('Agency Legal Name') }}</label>
                                        <input type="text" class="form-control @error('agency_name') is-invalid @enderror" id="agency_name" name="agency_name" value="{{ old('agency_name') }}" required autofocus>
                                        @error('agency_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="registration_number" class="form-label">{{ __('Registration Number (optional)') }}</label>
                                        <input type="text" class="form-control @error('registration_number') is-invalid @enderror" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" placeholder="e.g., RC/DLA/2024/A/1234">
                                        @error('registration_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">{{ __('Business Email') }}</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">{{ __('Official Phone Number') }}</label>
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+237 XXXX XXXX" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="headquarters_city" class="form-label">{{ __('Headquarters City') }}</label>
                                        <input type="text" class="form-control @error('headquarters_city') is-invalid @enderror" id="headquarters_city" name="headquarters_city" value="{{ old('headquarters_city') }}" placeholder="e.g., Douala" required>
                                        @error('headquarters_city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="contact_person_name" class="form-label">{{ __('Contact Person Name') }}</label>
                                        <input type="text" class="form-control @error('contact_person_name') is-invalid @enderror" id="contact_person_name" name="contact_person_name" value="{{ old('contact_person_name') }}" required>
                                        @error('contact_person_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="contact_person_phone" class="form-label">{{ __('Contact Person Phone') }}</label>
                                        <input type="tel" class="form-control @error('contact_person_phone') is-invalid @enderror" id="contact_person_phone" name="contact_person_phone" value="{{ old('contact_person_phone') }}" placeholder="+237 XXXX XXXX" required>
                                        @error('contact_person_phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label for="password" class="form-label">{{ __('Password') }}</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">{{ __('Minimum 8 characters') }}</div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="password_confirmation" class="form-label">{{ __('Confirm Password') }}</label>
                                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" required>
                                        @error('password_confirmation')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            {{ __('I agree to the ') }}
                                            <a href="{{ route('legal.terms') }}" class="text-decoration-none fw-semibold">{{ __('Terms of Service') }}</a>
                                            {{ __(' and ') }}
                                            <a href="{{ route('legal.privacy') }}" class="text-decoration-none fw-semibold">{{ __('Privacy Policy') }}</a>
                                        </label>
                                        @error('terms')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="alert alert-info mb-4">
                                        <i class="bi bi-info-circle me-2"></i>
                                        {{ __('Your agency application will be reviewed. Once approved, you can manage branches, routes, trips, and bookings from your agency dashboard.') }}
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-primary">{{ __('Register Agency') }}</button>
                                    </div>
                                </form>

                                <p class="text-center mt-4 mb-0">
                                    {{ __('Already have an account?') }}
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
