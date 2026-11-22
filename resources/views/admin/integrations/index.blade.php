@extends('layouts.app')

@section('title', __('Integrations'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Integrations') }}</h1>
</div>

<div class="row">
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg bg-primary-subtle text-primary me-3">
                        <i class="bx bx-mail-send bx-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ __('Mailgun') }}</h5>
                        <small class="text-muted">{{ __('Transactional emails') }}</small>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning">{{ __('Unknown') }}</span>
                    <a href="{{ route('integrations.show', 'mailgun') }}" class="btn btn-sm btn-outline-primary">{{ __('Configure') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg bg-success-subtle text-success me-3">
                        <i class="bx bxl-whatsapp bx-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ __('Meta WhatsApp') }}</h5>
                        <small class="text-muted">{{ __('WhatsApp Business API') }}</small>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning">{{ __('Unknown') }}</span>
                    <a href="{{ route('integrations.show', 'meta_whatsapp') }}" class="btn btn-sm btn-outline-primary">{{ __('Configure') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg bg-info-subtle text-info me-3">
                        <i class="bx bx-bell bx-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ __('Firebase FCM') }}</h5>
                        <small class="text-muted">{{ __('Push notifications') }}</small>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning">{{ __('Unknown') }}</span>
                    <a href="{{ route('integrations.show', 'fcm') }}" class="btn btn-sm btn-outline-primary">{{ __('Configure') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg bg-warning-subtle text-warning me-3">
                        <i class="bx bx-grid bx-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ __('Boxo') }}</h5>
                        <small class="text-muted">{{ __('Mini apps platform') }}</small>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning">{{ __('Unknown') }}</span>
                    <a href="{{ route('integrations.show', 'boxo') }}" class="btn btn-sm btn-outline-primary">{{ __('Configure') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg bg-purple-subtle text-purple me-3">
                        <i class="bx bx-wifi bx-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ __('eSIM Provider') }}</h5>
                        <small class="text-muted">{{ __('eSIM connectivity') }}</small>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning">{{ __('Unknown') }}</span>
                    <a href="{{ route('integrations.show', 'esim_provider') }}" class="btn btn-sm btn-outline-primary">{{ __('Configure') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection