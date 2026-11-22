@extends('layouts.app')

@section('title', __('Profile'))
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h1 class="h3 mb-4">{{ __('Profile') }}</h1>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="ely-avatar">
                            <img src="{{ $user->photo_url ?? asset('assets/eatalyft/placeholders/avatar.svg') }}" alt="{{ $user->name }}" width="120" height="120" class="rounded-circle">
                        </div>
                        <h5 class="mt-2">{{ $user->name }}</span>
                        <p class="text-muted small">{{ $user->email }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Edit Profile') }}</h5>
                    </div>
                    <div class="card-body">
                        @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                        @endif

                        <form action="{{ route('dashboard.profile.update') }}" method="POST">
                            @csrf
                            <div class="row mb-3">
                                <label class="form-label">{{ __('First Name') }}</label>
                                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
                            </div>
                            <div class="row mb-3">
                                <label class="form-label">{{ __('Last Name') }}</label>
                                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
                            </div>
                            <div class="row mb-3">
                                <label class="form-label">{{ __('Email Address') }}</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                                <div class="form-text">{{ __('Changing your email will require re-verification.') }}</div>
                            </div>
                            <div class="row mb-3">
                                <label class="form-label">{{ __('Phone Number') }}</label>
                                <input type="tel" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+237 XXXX XXXX" required>
                            </div>
                            <div class="row mb-3">
                                <label class="form-label">{{ __('Full Name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="row mb-3">
                                <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection