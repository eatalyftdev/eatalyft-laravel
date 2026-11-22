@extends('layouts.public')

@section('title', __('Cookie Policy - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('Cookie Policy') }}</h1>
        <p>{{ __('Last updated: :date', ['date' => now()->format('F j, Y')]) }}</p>
    </div>
</header>

<section class="service-detail" aria-labelledby="cookies-heading">
    <div class="container">
        <div class="content">
            <h2 id="cookies-heading">{{ __('1. What Are Cookies') }}</h2>
            <p>{{ __('Cookies are small text files stored on your device when you visit websites. They help us provide, protect, and improve our services.') }}</p>
            
            <h2>{{ __('2. Types of Cookies We Use') }}</h2>
            <h3>{{ __('Essential Cookies') }}</h3>
            <p>{{ __('Required for the website to function. Cannot be disabled.') }}</p>
            <ul>
                <li>{{ __('Session management') }}</li>
                <li>{{ __('CSRF protection') }}</li>
                <li>{{ __('Load balancing') }}</li>
            </ul>
            
            <h3>{{ __('Preferences Cookies') }}</h3>
            <p>{{ __('Remember your settings and preferences.') }}</p>
            <ul>
                <li>{{ __('Language selection') }}</li>
                <li>{{ __('Theme preference') }}</li>
                <li>{{ __('Currency preference') }}</li>
            </ul>
            
            <h3>{{ __('Analytics Cookies') }}</h3>
            <p>{{ __('Help us understand how visitors interact with our site.') }}</p>
            <ul>
                <li>{{ __('Page views') }}</li>
                <li>{{ __('Traffic sources') }}</li>
                <li>{{ __('User flow') }}</li>
            </ul>
            
            <h3>{{ __('Marketing Cookies') }}</h3>
            <p>{{ __('Used to deliver relevant ads and track campaign performance.') }}</p>
            
            <h2>{{ __('3. Third-Party Cookies') }}</h2>
            <p>{{ __('We use cookies from:') }}</p>
            <ul>
                <li>{{ __('Cloudflare (security, performance)') }}</li>
                <li>{{ __('Google Analytics (analytics)') }}</li>
                <li>{{ __('Stripe (payments)') }}</li>
            </ul>
            
            <h2>{{ __('4. Your Choices') }}</h2>
            <p>{{ __('You can manage cookies through your browser settings. Disabling essential cookies may break site functionality.') }}</p>
            
            <h2>{{ __('5. Contact') }}</h2>
            <p>{{ __('For questions about this policy, contact privacy@eatalyft.com.') }}</p>
        </div>
    </div>
</section>
@endsection