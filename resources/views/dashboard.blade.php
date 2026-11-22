@extends('layouts.app')

@section('title', __('Dashboard'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">{{ __('Dashboard') }}</h1>
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bx bx-currency-exchange me-1"></i> {{ Session::get('currency', config('currencies.default', 'XAF')) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @foreach(config('currencies.supported') as $currency)
                            <li>
                                <a class="dropdown-item {{ Session::get('currency') === $currency ? 'active' : '' }}" 
                                   href="#" 
                                   onclick="switchCurrency('{{ $currency }}')">
                                    {{ $currency }} - {{ config("currencies.symbols.$currency", $currency) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bx bx-globe me-1"></i> {{ strtoupper(app()->getLocale()) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="#" onclick="switchLanguage('en')">English</a></li>
                        <li><a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="#" onclick="switchLanguage('fr')">Français</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Total Users') }}</h6>
                        <h3 class="mb-0">{{ \App\Models\User::count() }}</h3>
                    </div>
                    <div class="avatar avatar-lg bg-primary-subtle text-primary">
                        <i class="bx bx-user bx-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Active Users') }}</h6>
                        <h3 class="mb-0">{{ \App\Models\User::where('status', 'active')->count() }}</h3>
                    </div>
                    <div class="avatar avatar-lg bg-success-subtle text-success">
                        <i class="bx bx-check-circle bx-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Roles') }}</h6>
                        <h3 class="mb-0">{{ \Spatie\Permission\Models\Role::count() }}</h3>
                    </div>
                    <div class="avatar avatar-lg bg-info-subtle text-info">
                        <i class="bx bx-shield bx-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Permissions') }}</h6>
                        <h3 class="mb-0">{{ \Spatie\Permission\Models\Permission::count() }}</h3>
                    </div>
                    <div class="avatar avatar-lg bg-warning-subtle text-warning">
                        <i class="bx bx-key bx-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Quick Actions') }}</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @can('users.read')
                        <div class="col-md-3">
                            <a href="{{ route('users.index') }}" class="btn btn-outline-primary w-100 p-3 text-start">
                                <i class="bx bx-user bx-md mb-2"></i>
                                <div>{{ __('Manage Users') }}</div>
                            </a>
                        </div>
                    @endcan

                    @can('roles.read')
                        <div class="col-md-3">
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-primary w-100 p-3 text-start">
                                <i class="bx bx-shield bx-md mb-2"></i>
                                <div>{{ __('Roles & Permissions') }}</div>
                            </a>
                        </div>
                    @endcan

                    @can('kyc.read')
                        <div class="col-md-3">
                            <a href="{{ route('kyc.index') }}" class="btn btn-outline-primary w-100 p-3 text-start">
                                <i class="bx bx-shield-check bx-md mb-2"></i>
                                <div>{{ __('KYC Management') }}</div>
                            </a>
                        </div>
                    @endcan

                    @can('audit.read')
                        <div class="col-md-3">
                            <a href="{{ route('audit.index') }}" class="btn btn-outline-primary w-100 p-3 text-start">
                                <i class="bx bx-clipboard-data bx-md mb-2"></i>
                                <div>{{ __('Audit Logs') }}</div>
                            </a>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchCurrency(currency) {
    fetch('{{ route("currency.switch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ currency }),
    }).then(response => response.json()).then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function switchLanguage(locale) {
    fetch('{{ route("language.switch") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ locale }),
    }).then(response => response.json()).then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>
@endpush