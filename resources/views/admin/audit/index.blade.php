@extends('layouts.app')

@section('title', __('Audit Logs'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Audit Logs') }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Actor') }}</th>
                        <th>{{ __('Target') }}</th>
                        <th>{{ __('Details') }}</th>
                        <th>{{ __('Created') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ __('No audit logs found') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection