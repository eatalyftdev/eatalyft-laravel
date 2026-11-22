@extends('layouts.app')

@section('title', __('FX Rates'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Exchange Rates') }}</h1>
    @can('fx.rates.write')
        <a href="{{ route('fx.rates.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> {{ __('Add Rate') }}
        </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('From') }}</th>
                        <th>{{ __('To') }}</th>
                        <th>{{ __('Rate') }}</th>
                        <th>{{ __('Margin %') }}</th>
                        <th>{{ __('Source') }}</th>
                        <th>{{ __('Updated') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted">{{ __('No exchange rates found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection