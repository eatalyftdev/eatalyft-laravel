@extends('layouts.app')

@section('title', __('KYC Reviews'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('KYC Reviews') }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Tier') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Submitted') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ __('No KYC reviews found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection