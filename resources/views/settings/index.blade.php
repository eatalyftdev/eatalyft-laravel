@extends('layouts.app')

@section('title', __('Settings'))

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="h3 mb-4">{{ __('Settings') }}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">{{ __('Account Settings') }}</div>
            <div class="card-body">
                <form id="settingsForm">
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('First Name') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="first_name" value="{{ auth()->user()->first_name }}">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Last Name') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="last_name" value="{{ auth()->user()->last_name }}">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Phone') }}</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" name="phone" value="{{ auth()->user()->phone }}">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Currency') }}</label>
                        <div class="col-sm-10">
                            <select class="form-select" name="currency_preference">
                                @foreach(config('currencies.supported') as $currency)
                                    <option value="{{ $currency }}" {{ auth()->user()->currency_preference === $currency ? 'selected' : '' }}>
                                        {{ $currency }} - {{ config("currencies.symbols.$currency", $currency) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Language') }}</label>
                        <div class="col-sm-10">
                            <select class="form-select" name="locale">
                                <option value="en" {{ app()->getLocale() === 'en' ? 'selected' : '' }}>English</option>
                                <option value="fr" {{ app()->getLocale() === 'fr' ? 'selected' : '' }}>Français</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-10 offset-sm-2">
                            <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">{{ __('Change Password') }}</div>
            <div class="card-body">
                <form id="passwordForm">
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Current Password') }}</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('New Password') }}</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password" required minlength="8">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-sm-2 col-form-label">{{ __('Confirm New Password') }}</label>
                        <div class="col-sm-10">
                            <input type="password" class="form-control" name="password_confirmation" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-10 offset-sm-2">
                            <button type="submit" class="btn btn-warning">{{ __('Change Password') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">{{ __('Notification Preferences') }}</div>
            <div class="card-body">
                <form id="notificationsForm">
                    <div class="row mb-3">
                        <div class="col-sm-10 offset-sm-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications" checked>
                                <label class="form-check-label" for="email_notifications">{{ __('Email Notifications') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-10 offset-sm-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="push_notifications" id="push_notifications" checked>
                                <label class="form-check-label" for="push_notifications">{{ __('Push Notifications') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-10 offset-sm-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="sms_notifications" id="sms_notifications">
                                <label class="form-check-label" for="sms_notifications">{{ __('SMS Notifications') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-10 offset-sm-2">
                            <button type="submit" class="btn btn-secondary">{{ __('Save Preferences') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">{{ __('Account Status') }}</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">{{ __('Status') }}</dt>
                    <dd class="col-sm-6"><span class="badge bg-{{ auth()->user()->status === 'active' ? 'success' : (auth()->user()->status === 'pending' ? 'warning' : 'danger') }}">{{ ucfirst(auth()->user()->status) }}</span></dd>

                    <dt class="col-sm-6">{{ __('Role') }}</dt>
                    <dd class="col-sm-6">{{ ucfirst(auth()->user()->role) }}</dd>

                    <dt class="col-sm-6">{{ __('KYC Tier') }}</dt>
                    <dd class="col-sm-6">Tier {{ auth()->user()->kyc_tier }}</dd>

                    <dt class="col-sm-6">{{ __('KYC Status') }}</dt>
                    <dd class="col-sm-6">{{ auth()->user()->kyc_status }}</dd>

                    <dt class="col-sm-6">{{ __('2FA') }}</dt>
                    <dd class="col-sm-6"><span class="badge bg-secondary">{{ __('Not Enabled') }}</span></dd>

                    <dt class="col-sm-6">{{ __('Email Verified') }}</dt>
                    <dd class="col-sm-6">{{ auth()->user()->email_verified_at ? __('Yes') : __('No') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('settingsForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const response = await fetch('{{ route("settings.update") }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify(data),
    });
    
    if (response.ok) {
        location.reload();
    }
});

document.getElementById('passwordForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const response = await fetch('{{ route("password.update") }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify(data),
    });
    
    if (response.ok) {
        alert('Password changed successfully');
        e.target.reset();
    }
});
</script>
@endpush