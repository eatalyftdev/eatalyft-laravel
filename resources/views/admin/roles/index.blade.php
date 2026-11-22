@extends('layouts.app')

@section('title', __('Roles & Permissions'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Roles & Permissions') }}</h1>
    @can('roles.write')
        <a href="{{ route('roles.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> {{ __('Add Role') }}
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
                        <th>{{ __('Guard') }}</th>
                        <th>{{ __('Permissions') }}</th>
                        <th>{{ __('Created') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ __('No roles found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection