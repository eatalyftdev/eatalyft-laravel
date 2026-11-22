@extends('layouts.public')

@section('title', __('FAQ - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('Frequently Asked Questions') }}</h1>
        <p>{{ __('Quick answers to common questions.') }}</p>
    </div>
</header>

<section class="faq" aria-labelledby="faq-main-heading">
    <div class="container">
        <h2 id="faq-main-heading" class="sr-only">{{ __('FAQ') }}</h2>
        <div class="accordion">
            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-1">
                    {{ __('How do I book a bus ticket?') }}
                    <span aria-hidden="true">+</span>
                </button>
                <div class="accordion-content" id="faq-1" role="region">
                    <p>{{ __('Select your route, choose a seat, and complete checkout. Your e-ticket with QR code arrives instantly in the app and via email.') }}</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-2">
                    {{ __('What is an eSIM and how does it work?') }}
                    <span aria-hidden="true">+</span>
                </button>
                <div class="accordion-content" id="faq-2" role="region">
                    <p>{{ __('An eSIM is a digital SIM that lets you activate a data plan without a physical card. After purchase, scan the QR code in your phone\'s settings to install. Works in 190+ countries.') }}</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-3">
                    {{ __('Can I cancel or modify my booking?') }}
                    <span aria-hidden="true">+</span>
                </button>
                <div class="accordion-content" id="faq-3" role="region">
                    <p>{{ __('Most bookings can be modified or cancelled up to 2 hours before departure. Check the specific policy for your booking in the app. Refunds are processed to the original payment method.') }}</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-4">
                    {{ __('How do gift cards work?') }}
                    <span aria-hidden="true">+</span>
                </button>
                <div class="accordion-content" id="faq-4" role="region">
                    <p>{{ __('Purchase a gift card for any amount, share the unique code with the recipient, and they apply it at checkout. Gift cards never expire and work across all services.') }}</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-5">
                    {{ __('Is my payment information secure?') }}
                    <span aria-hidden="true">+</span>
                </button>
                <div class="accordion-content" id="faq-5" role="region">
                    <p>{{ __('Yes. We use Stripe for payment processing and never store your full card details. All transactions are encrypted and PCI DSS compliant.') }}</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-6">
                    {{ __('Do I need an account to book?') }}
                    <span aria-hidden="true">+</span>
                </button>
                <div class="accordion-content" id="faq-6" role="region">
                    <p>{{ __('You can book as a guest, but creating an account gives you access to booking history, saved preferences, loyalty rewards, and faster checkout.') }}</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-7">
                    {{ __('How do I contact support?') }}
                    <span aria-hidden="true">+</span>
                </button>
                <div class="accordion-content" id="faq-7" role="region">
                    <p>{{ __('Visit our') }} <a href="{{ route('contact') }}">{{ __('Contact page') }}</a>, {{ __('email support@eatalyft.com, or use the in-app chat. Support is available 24/7 for urgent issues.') }}</p>
                </div>
            </div>
            
            <div class="accordion-item">
                <button class="accordion-trigger" aria-expanded="false" aria-controls="faq-8">
                    {{ __('What countries are supported for eSIM?') }}
                    <span aria-hidden="true">+</span>
                </button>
                <div class="accordion-content" id="faq-8" role="region">
                    <p>{{ __('Our eSIMs work in 190+ countries worldwide. Check the coverage map during purchase for specific country availability and data plans.') }}</p>
                </div>
            </div>
        </div>
        
        <div style="margin-top: var(--space-12); text-align: center;">
            <p>{{ __('Still have questions?') }} <a href="{{ route('contact') }}" class="cta-pill" style="display: inline-block; margin-left: var(--space-4);">{{ __('Contact us') }}</a></p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.accordion-trigger').forEach(btn => {
        btn.addEventListener('click', () => {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', !expanded);
            const content = document.getElementById(btn.getAttribute('aria-controls'));
            if (content) {
                content.classList.toggle('open', !expanded);
            }
        });
    });
</script>
@endpush