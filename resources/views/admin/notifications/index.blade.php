@extends('layouts.app')

@section('title', __('Notifications'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Notifications') }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Channel') }}</th>
                        <th>{{ __('Recipient') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Sent At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">{{ __('No notifications found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection