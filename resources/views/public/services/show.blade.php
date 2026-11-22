@extends('layouts.public')

@section('title', __(':service_name - :app_name', ['service_name' => $service->title ?? __('Service'), 'app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ $service->title ?? __('Service') }}</h1>
        <p class="meta">{{ $service->description ?? __('Learn more about this service.') }}</p>
    </div>
</header>

<section class="service-detail" aria-labelledby="service-detail-heading">
    <div class="container">
        <div class="content">
            @if($service->content)
                {!! $service->content !!}
            @else
                <h2>{{ __('Overview') }}</h2>
                <p>{{ __('Detailed information about this service coming soon.') }}</p>
                
                <h2>{{ __('Features') }}</h2>
                <ul>
                    <li>{{ __('Instant booking') }}</li>
                    <li>{{ __('Secure payments') }}</li>
                    <li>{{ __('24/7 support') }}</li>
                </ul>
                
                <h2>{{ __('How it works') }}</h2>
                <ol>
                    <li>{{ __('Choose your service') }}</li>
                    <li>{{ __('Enter details') }}</li>
                    <li>{{ __('Confirm and pay') }}</li>
                    <li>{{ __('Receive confirmation') }}</li>
                </ol>
            @endif
        </div>
        
        <div class="final-cta" style="margin-top: var(--space-16);">
            <h2>{{ __('Ready to book?') }}</h2>
            <a href="{{ route('login') }}" class="cta-pill">{{ __('Book now') }}</a>
        </div>
    </div>
</section>
@endsection