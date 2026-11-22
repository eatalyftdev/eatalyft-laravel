@extends('layouts.app')

@section('title', __('Hotels'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Hotels') }}</h1>
    @can('hotels.write')
        <a href="{{ route('hotels.create') }}" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i> {{ __('Add Hotel') }}
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
                        <th>{{ __('City') }}</th>
                        <th>{{ __('Stars') }}</th>
                        <th>{{ __('Rooms') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center text-muted">{{ __('No hotels found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection