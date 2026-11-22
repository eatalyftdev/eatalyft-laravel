@extends('layouts.public')

@section('title', __('Search Results - :app_name', ['app_name' => config('app.name')]))

@section('content')
<header class="page-header">
    <div class="container">
        <h1>{{ __('Search Results') }}</h1>
        <p>{{ __('Showing results for: :query', ['query' => $query ?? __('all')]) }}</p>
    </div>
</header>

<section class="service-detail" aria-labelledby="search-heading">
    <div class="container">
        <div class="content">
            @if($service)
                <h2 id="search-heading">{{ __('Service: :service', ['service' => ucfirst(str_replace('_', ' ', $service))]) }}</h2>
            @else
                <h2 id="search-heading">{{ __('All Services') }}</h2>
            @endif
            
            <p>{{ __('Search functionality coming soon. This will integrate with the booking engine.') }}</p>
            
            <div class="final-cta" style="margin-top: var(--space-8);">
                <a href="{{ route('services.index') }}" class="cta-pill">{{ __('Browse all services') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection