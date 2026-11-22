@extends('layouts.app')

@section('title', __('Currencies'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Currencies') }}</h1>
    @can('currencies.write')
        <a href="{{ route('currencies.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> {{ __('Add Currency') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Symbol') }}</th>
                        <th>{{ __('Decimals') }}</th>
                        <th>{{ __('Default') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">{{ __('No currencies found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection