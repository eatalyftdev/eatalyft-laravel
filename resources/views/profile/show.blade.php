@extends('layouts.app')

@section('title', __('Profile'))

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">{{ __('Profile') }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <x-eatalyft.image
                    src="{{ auth()->user()->photo_url }}"
                    manifestKey="placeholders.avatar"
                    alt="{{ auth()->user()->name }}"
                    width="150"
                    height="150"
                    class="rounded-circle img-fluid mb-3"
                    objectFit="cover"
                />
                <h4>{{ auth()->user()->name }}</h4>
                <p class="text-muted">{{ auth()->user()->email }}</p>
                <span class="badge bg-{{ auth()->user()->status === 'active' ? 'success' : (auth()->user()->status === 'pending' ? 'warning' : 'danger') }}">
                    {{ ucfirst(auth()->user()->status) }}
                </span>
                <div class="mt-2">
                    <span class="badge bg-info">{{ ucfirst(auth()->user()->role) }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">{{ __('Account Information') }}</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">{{ __('Role') }}</dt>
                    <dd class="col-sm-8">{{ ucfirst(auth()->user()->role) }}</dd>

                    <dt class="col-sm-4">{{ __('Status') }}</dt>
                    <dd class="col-sm-8">{{ ucfirst(auth()->user()->status) }}</dd>

                    <dt class="col-sm-4">{{ __('KYC Tier') }}</dt>
                    <dd class="col-sm-8">Tier {{ auth()->user()->kyc_tier }}</dd>

                    <dt class="col-sm-4">{{ __('KYC Status') }}</dt>
                    <dd class="col-sm-8">{{ auth()->user()->kyc_status }}</dd>

                    <dt class="col-sm-4">{{ __('Currency') }}</dt>
                    <dd class="col-sm-8">{{ auth()->user()->currency_preference }}</dd>

                    <dt class="col-sm-4">{{ __('Member Since') }}</dt>
                    <dd class="col-sm-8">{{ auth()->user()->created_at->format('M d, Y') }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">{{ __('Personal Details') }}</div>
            <div class="card-body">
                <form>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('First Name') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="{{ auth()->user()->first_name }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Last Name') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="{{ auth()->user()->last_name }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Phone') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="{{ auth()->user()->phone }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Email') }}</label>
                        <div class="col-sm-10">
                            <input type="email" class="form-control" value="{{ auth()->user()->email }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Public ID') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="{{ auth()->user()->public_id }}" readonly>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Firebase UID') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" value="{{ auth()->user()->firebase_uid }}" readonly>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-10 offset-sm-2">
                            <a href="{{ route('settings.index') }}" class="btn btn-primary">{{ __('Edit Profile') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection