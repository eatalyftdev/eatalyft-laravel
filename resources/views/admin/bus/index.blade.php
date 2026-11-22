@extends('layouts.app')

@section('title', __('Bus Management'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Bus Management') }}</h1>
    @can('bus.routes.write')
        <a href="{{ route('bus.routes.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> {{ __('Add Route') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Origin') }}</th>
                        <th>{{ __('Destination') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center text-muted">{{ __('No bus resources found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection