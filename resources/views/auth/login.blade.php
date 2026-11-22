<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Eatalyft') }} - {{ __('Login') }}</title>
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

                                <h4 class="text-center mb-3">{{ __('Welcome back') }}</h4>
                                <p class="text-center text-muted mb-4">{{ __('Sign in with a one-time code') }}</p>

                                @if (session('status'))
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        {{ session('status') }}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                    </div>
                                @endif

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

                                <form method="POST" action="{{ route('login.otp.request') }}" id="login-step-1">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="contact" class="form-label">{{ __('Email or Phone') }}</label>
                                        <input type="text" class="form-control @error('contact') is-invalid @enderror" id="contact" name="contact" value="{{ old('contact') }}" required autofocus autocomplete="email">
                                        @error('contact')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">{{ __('We\'ll send a one-time code to this contact') }}</div>
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-primary">{{ __('Send Code') }}</button>
                                    </div>
                                </form>

                                <form method="POST" action="{{ route('login.otp.verify') }}" id="login-step-2" style="display: none;">
                                    @csrf
                                    <input type="hidden" name="contact" id="login-contact">

                                    <div class="mb-3">
                                        <label for="otp" class="form-label">{{ __('One-Time Code') }}</label>
                                        <input type="text" class="form-control @error('otp') is-invalid @enderror" id="otp" name="otp" maxlength="6" required autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*">
                                        @error('otp')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">{{ __('Enter the 6-digit code sent to <span id="login-contact-display"></span>') }}</div>
                                    </div>

                                    <div class="d-grid mb-3">
                                        <button type="submit" class="btn btn-primary">{{ __('Verify & Sign In') }}</button>
                                    </div>

                                    <div class="text-center mb-3">
                                        <button type="button" class="btn btn-link" id="resend-otp" disabled>{{ __('Resend code') }} <span id="resend-timer"></span></button>
                                    </div>

                                    <div class="text-center">
                                        <button type="button" class="btn btn-link" id="back-to-step-1">{{ __('Change contact') }}</button>
                                    </div>
                                </form>

                                <p class="text-center mt-4 mb-0">
                                    {{ __('Don\'t have an account?') }}
                                    <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">{{ __('Sign up') }}</a>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const step1 = document.getElementById('login-step-1');
            const step2 = document.getElementById('login-step-2');
            const resendBtn = document.getElementById('resend-otp');
            const resendTimer = document.getElementById('resend-timer');
            const backBtn = document.getElementById('back-to-step-1');
            const contactDisplay = document.getElementById('login-contact-display');
            const contactInput = document.getElementById('login-contact');
            let resendCountdown = 0;

            step1.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(step1);
                
                try {
                    const response = await fetch(step1.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok) {
                        contactInput.value = formData.get('contact');
                        contactDisplay.textContent = formData.get('contact');
                        step1.style.display = 'none';
                        step2.style.display = 'block';
                        startResendTimer();
                    } else {
                        if (data.errors) {
                            showErrors(data.errors);
                        } else {
                            alert(data.message || 'Error sending code');
                        }
                    }
                } catch (err) {
                    console.error(err);
                    alert('An error occurred');
                }
            });

            step2.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(step2);
                
                try {
                    const response = await fetch(step2.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok) {
                        window.location.href = data.redirect || '/dashboard';
                    } else {
                        if (data.errors) {
                            showErrors(data.errors, step2);
                        } else {
                            alert(data.message || 'Error verifying code');
                        }
                    }
                } catch (err) {
                    console.error(err);
                    alert('An error occurred');
                }
            });

            resendBtn.addEventListener('click', async () => {
                if (resendCountdown > 0) return;
                
                const contact = contactInput.value;
                try {
                    const response = await fetch('{{ route('login.otp.request') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: new URLSearchParams({ contact })
                    });
                    
                    if (response.ok) {
                        startResendTimer();
                    }
                } catch (err) {
                    console.error(err);
                }
            });

            backBtn.addEventListener('click', () => {
                step2.style.display = 'none';
                step1.style.display = 'block';
                clearInterval(resendTimer.interval);
            });

            function startResendTimer() {
                resendCountdown = 60;
                resendBtn.disabled = true;
                const interval = setInterval(() => {
                    resendCountdown--;
                    resendTimer.textContent = ` (${resendCountdown}s)`;
                    if (resendCountdown <= 0) {
                        clearInterval(interval);
                        resendBtn.disabled = false;
                        resendTimer.textContent = '';
                    }
                }, 1000);
                resendTimer.interval = interval;
            }

            function showErrors(errors, form = step1) {
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                
                Object.entries(errors).forEach(([field, messages]) => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback';
                        feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                        input.parentNode.appendChild(feedback);
                    }
                });
            }
        });
    </script>
</body>
</html>