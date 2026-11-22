@extends('layouts.app')

@section('title', __('Boxo Mini Apps'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Boxo Mini Apps') }}</h1>
    @can('boxo.miniapps.write')
        <a href="{{ route('boxo.miniapps.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> {{ __('Add Mini App') }}
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
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center text-muted">{{ __('No mini apps found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection