@extends('layouts.public')

@section('title', __('Privacy Policy - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('Privacy Policy') }}</h1>
        <p>{{ __('Last updated: :date', ['date' => now()->format('F j, Y')]) }}</p>
    </div>
</header>

<section class="service-detail" aria-labelledby="privacy-heading">
    <div class="container">
        <div class="content">
            <h2 id="privacy-heading">{{ __('1. Data We Collect') }}</h2>
            <p>{{ __('We collect information you provide directly: name, email, phone, payment details, booking history, and preferences.') }}</p>
            
            <h2>{{ __('2. How We Use Your Data') }}</h2>
            <ul>
                <li>{{ __('Process bookings and deliver e-tickets') }}</li>
                <li>{{ __('Send booking confirmations and updates') }}</li>
                <li>{{ __('Improve our services and personalize your experience') }}</li>
                <li>{{ __('Comply with legal obligations') }}</li>
            </ul>
            
            <h2>{{ __('3. Data Sharing') }}</h2>
            <p>{{ __('We share data only with:') }}</p>
            <ul>
                <li>{{ __('Verified partners to fulfill your bookings') }}</li>
                <li>{{ __('Payment processors (Stripe)') }}</li>
                <li>{{ __('Legal authorities when required') }}</li>
            </ul>
            <p>{{ __('We never sell your personal data.') }}</p>
            
            <h2>{{ __('4. Data Retention') }}</h2>
            <p>{{ __('We retain booking data for 7 years for legal compliance. Account data is kept while your account is active.') }}</p>
            
            <h2>{{ __('5. Your Rights') }}</h2>
            <p>{{ __('Under GDPR and CCPA, you have the right to:') }}</p>
            <ul>
                <li>{{ __('Access your data') }}</li>
                <li>{{ __('Rectify inaccurate data') }}</li>
                <li>{{ __('Request deletion') }}</li>
                <li>{{ __('Restrict processing') }}</li>
                <li>{{ __('Data portability') }}</li>
                <li>{{ __('Object to processing') }}</li>
            </ul>
            
            <h2>{{ __('6. Security') }}</h2>
            <p>{{ __('We implement encryption, access controls, and regular security audits. All payments are PCI DSS compliant.') }}</p>
            
            <h2>{{ __('7. International Transfers') }}</h2>
            <p>{{ __('Your data may be processed in the USA and EU. We use Standard Contractual Clauses for transfers.') }}</p>
            
            <h2>{{ __('8. Cookies') }}</h2>
            <p>{{ __('See our') }} <a href="{{ route('legal.cookies') }}">{{ __('Cookie Policy') }}</a> {{ __('for details on cookie usage.') }}</p>
            
            <h2>{{ __('9. Children\'s Privacy') }}</h2>
            <p>{{ __('Our services are not directed to children under 16. We do not knowingly collect data from children.') }}</p>
            
            <h2>{{ __('10. Contact') }}</h2>
            <p>{{ __('For privacy inquiries, contact privacy@eatalyft.com or our Data Protection Officer at dpo@eatalyft.com.') }}</p>
        </div>
    </div>
</section>
@endsection