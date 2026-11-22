@extends('layouts.app')

@section('title', __('Error Monitoring'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Error Monitoring') }}</h1>
    <a href="{{ route('admin.monitoring.errors.index') }}" class="btn btn-outline-secondary">
        <i class="bx bx-refresh me-1"></i> {{ __('Refresh') }}
    </a>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ __('Total Incidents') }}</h5>
                <p class="card-text display-4 text-primary">{{ $stats['total'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-warning">
            <div class="card-body">
                <h5 class="card-title">{{ __('Open Incidents') }}</h5>
                <p class="card-text display-4 text-dark">{{ $stats['open'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-danger">
            <div class="card-body">
                <h5 class="card-title">{{ __('Critical Incidents') }}</h5>
                <p class="card-text display-4 text-dark">{{ $stats['critical'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card bg-success">
            <div class="card-body">
                <h5 class="card-title">{{ __('Resolved Incidents') }}</h5>
                <p class="card-text display-4 text-dark">{{ $stats['resolved'] }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.monitoring.errors.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('Status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('Severity') }}</label>
                <select name="severity" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach($severities as $s)
                        <option value="{{ $s }}" {{ request('severity') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('Environment') }}</label>
                <select name="environment" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach($environments as $env)
                        <option value="{{ $env }}" {{ request('environment') === $env ? 'selected' : '' }}>{{ $env }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('Search') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('Search exception, message, or fingerprint') }}" value="{{ request('search') }}">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">{{ __('Filter') }}</button>
                <a href="{{ route('admin.monitoring.errors.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- Incidents Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>{{ __('Severity') }}</th>
                        <th>{{ __('Exception') }}</th>
                        <th>{{ __('Message') }}</th>
                        <th>{{ __('Environment') }}</th>
                        <th>{{ __('Occurrences') }}</th>
                        <th>{{ __('First Seen') }}</th>
                        <th>{{ __('Last Seen') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if($incidents->isEmpty())
                        <tr>
                            <td colspan="9" class="text-center text-muted">{{ __('No error incidents found') }}</td>
                        </tr>
                    @else
                        @foreach($incidents as $incident)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $incident->severity === 'critical' ? 'danger' : ($incident->severity === 'error' ? 'warning' : 'info') }}">
                                        {{ ucfirst($incident->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ $incident->exception_class }}</small>
                                </td>
                                <td>
                                    <small>{{ $incident->message }}</small>
                                </td>
                                <td>
                                    <small>{{ $incident->environment }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $incident->occurrence_count }}</span>
                                </td>
                                <td>
                                    <small>{{ $incident->first_seen_at?->format('Y-m-d H:i') }}</small>
                                </td>
                                <td>
                                    <small>{{ $incident->last_seen_at?->format('Y-m-d H:i') }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $incident->status === 'resolved' ? 'success' : ($incident->status === 'investigating' ? 'primary' : 'warning') }}">
                                        {{ ucfirst($incident->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('admin.monitoring.errors.show', $incident) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bx bx-show me-1"></i>
                                        </a>
                                        @if($incident->status === 'open')
                                            <form method="POST" action="{{ route('admin.monitoring.errors.investigating', $incident) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    <i class="bx bx-edit me-1"></i>
                                                </button>
                                            </form>
                                        @elseif($incident->status === 'investigating')
                                            <form method="POST" action="{{ route('admin.monitoring.errors.resolve', $incident) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success">
                                                    <i class="bx bx-check me-1"></i>
                                                </button>
                                            </form>
                                        @elseif($incident->status === 'resolved')
                                            <form method="POST" action="{{ route('admin.monitoring.errors.reopen', $incident) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                                    <i class="bx bx-refresh me-1"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.monitoring.errors.destroy', $incident) }}" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bx bx-trash me-1"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        {{ $incidents->links() }}
    </div>
</div>
@endsection