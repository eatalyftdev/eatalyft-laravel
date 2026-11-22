@extends('layouts.public')

@section('title', __('About - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('About :app_name', ['app_name' => config('app.name')]) }}</h1>
        <p>{{ __('Making travel and daily services accessible to everyone.') }}</p>
    </div>
</header>

<section class="service-detail" aria-labelledby="about-heading">
    <div class="container">
        <div class="content">
            <h2 id="about-heading">{{ __('Our Story') }}</h2>
            <p>{{ __('Founded in 2024, Eatalyft started with a simple mission: to make booking bus tickets as easy as ordering a ride. Today, we\'ve expanded to offer flights, eSIMs, gift cards, hotels, and on-demand services — all in one app.') }}</p>
            
            <h2>{{ __('Our Values') }}</h2>
            <ul>
                <li><strong>{{ __('Transparency') }}</strong> — {{ __('No hidden fees, no surprises.') }}</li>
                <li><strong>{{ __('Trust') }}</strong> — {{ __('Every partner is KYC-verified.') }}</li>
                <li><strong>{{ __('Simplicity') }}</strong> — {{ __('Three taps from search to ticket.') }}</li>
                <li><strong>{{ __('Accessibility') }}</strong> — {{ __('Built for everyone, everywhere.') }}</li>
            </ul>
            
            <h2>{{ __('Our Team') }}</h2>
            <p>{{ __('A diverse team of travelers, engineers, and problem-solvers across three continents.') }}</p>
            
            <h2>{{ __('Press & Media') }}</h2>
            <p>{{ __('For media inquiries, please contact <a href="mailto:press@eatalyft.com">press@eatalyft.com</a>.') }}</p>
        </div>
    </div>
</section>

<section class="trust" aria-labelledby="team-heading">
    <div class="container">
        <h2 id="team-heading">{{ __('Meet the founders') }}</h2>
        <div class="highlight-cards">
            <article class="highlight-card">
                <div class="icon" aria-hidden="true">👤</div>
                <h3>{{ __('CEO') }}</h3>
                <p>{{ __('Former product lead at a major travel platform.') }}</p>
            </article>
            <article class="highlight-card">
                <div class="icon" aria-hidden="true">👤</div>
                <h3>{{ __('CTO') }}</h3>
                <p>{{ __('Ex-engineering director at a fintech unicorn.') }}</p>
            </article>
            <article class="highlight-card">
                <div class="icon" aria-hidden="true">👤</div>
                <h3>{{ __('COO') }}</h3>
                <p>{{ __('Operations expert with 15+ years in logistics.') }}</p>
            </article>
        </div>
    </div>
</section>
@endsection