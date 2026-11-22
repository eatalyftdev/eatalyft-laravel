@extends('layouts.public')

@section('title', __('Terms of Service - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('Terms of Service') }}</h1>
        <p>{{ __('Last updated: :date', ['date' => now()->format('F j, Y')]) }}</p>
    </div>
</header>

<section class="service-detail" aria-labelledby="terms-heading">
    <div class="container">
        <div class="content">
            <h2 id="terms-heading">{{ __('1. Acceptance of Terms') }}</h2>
            <p>{{ __('By accessing and using :app_name, you accept and agree to be bound by the terms and provision of this agreement.', ['app_name' => config('app.name')]) }}</p>
            
            <h2>{{ __('2. Services') }}</h2>
            <p>{{ __('We provide a platform for booking bus tickets, flights, eSIMs, gift cards, hotels, and on-demand services through verified partners.') }}</p>
            
            <h2>{{ __('3. User Accounts') }}</h2>
            <p>{{ __('You must provide accurate information when creating an account. You are responsible for maintaining the confidentiality of your account credentials.') }}</p>
            
            <h2>{{ __('4. Bookings & Payments') }}</h2>
            <p>{{ __('All bookings are subject to partner terms. Prices are displayed in your local currency. Refunds follow partner cancellation policies.') }}</p>
            
            <h2>{{ __('5. eSIM Service') }}</h2>
            <p>{{ __('eSIMs are delivered digitally. Compatibility with your device is your responsibility. No refunds after QR code generation.') }}</p>
            
            <h2>{{ __('6. Gift Cards') }}</h2>
            <p>{{ __('Gift cards never expire and are non-refundable. They can be used across all services on the platform.') }}</p>
            
            <h2>{{ __('7. Intellectual Property') }}</h2>
            <p>{{ __('All content, trademarks, and data on this platform are the property of :app_name or its licensors.', ['app_name' => config('app.name')]) }}</p>
            
            <h2>{{ __('8. Limitation of Liability') }}</h2>
            <p>{{ __('We are not liable for indirect, incidental, or consequential damages arising from use of the platform.') }}</p>
            
            <h2>{{ __('9. Governing Law') }}</h2>
            <p>{{ __('These terms are governed by the laws of Delaware, USA.') }}</p>
            
            <h2>{{ __('10. Changes to Terms') }}</h2>
            <p>{{ __('We may update these terms at any time. Continued use constitutes acceptance of changes.') }}</p>
            
            <h2>{{ __('Contact') }}</h2>
            <p>{{ __('For questions about these terms, contact legal@eatalyft.com.') }}</p>
        </div>
    </div>
</section>
@endsection