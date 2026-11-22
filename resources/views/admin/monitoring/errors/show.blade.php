@extends('layouts.app')

@section('title', __('Error Incident'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('Error Incident') }}</h1>
    <div>
        <a href="{{ route('admin.monitoring.errors.index') }}" class="btn btn-outline-secondary me-2">
            <i class="bx bx-arrow-back me-1"></i> {{ __('Back to List') }}
        </a>
        @if($incident->status === 'open')
            <form method="POST" action="{{ route('admin.monitoring.errors.investigating', $incident) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bx bx-edit me-1"></i> {{ __('Investigating') }}
                </button>
            </form>
        @elseif($incident->status === 'investigating')
            <form method="POST" action="{{ route('admin.monitoring.errors.resolve', $incident) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-success">
                    <i class="bx bx-check me-1"></i> {{ __('Resolve') }}
                </button>
            </form>
        @elseif($incident->status === 'resolved')
            <form method="POST" action="{{ route('admin.monitoring.errors.reopen', $incident) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning">
                    <i class="bx bx-refresh me-1"></i> {{ __('Reopen') }}
                </button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.monitoring.errors.destroy', $incident) }}" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger">
                <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Incident Details') }}</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">{{ __('Incident ID') }}</dt>
                    <dd class="col-sm-9">{{ $incident->uuid }}</dd>

                    <dt class="col-sm-3">{{ __('Severity') }}</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-{{ $incident->severity === 'critical' ? 'danger' : ($incident->severity === 'error' ? 'warning' : 'info') }}">
                            {{ strtoupper($incident->severity) }}
                        </span>
                    </dd>

                    <dt class="col-sm-3">{{ __('Status') }}</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-{{ $incident->status === 'resolved' ? 'success' : ($incident->status === 'investigating' ? 'primary' : 'warning') }}">
                            {{ ucfirst($incident->status) }}
                        </span>
                    </dd>

                    <dt class="col-sm-3">{{ __('Exception Class') }}</dt>
                    <dd class="col-sm-9"><code>{{ $incident->exception_class }}</code></dd>

                    <dt class="col-sm-3">{{ __('HTTP Status') }}</dt>
                    <dd class="col-sm-9">{{ $incident->http_status ?? 'N/A' }}</dd>

                    <dt class="col-sm-3">{{ __('Environment') }}</dt>
                    <dd class="col-sm-9">{{ $incident->environment }}</dd>

                    <dt class="col-sm-3">{{ __('Route') }}</dt>
                    <dd class="col-sm-9">{{ $incident->route_name ?? 'N/A' }}</dd>

                    <dt class="col-sm-3">{{ __('Request Method') }}</dt>
                    <dd class="col-sm-9">{{ $incident->request_method ?? 'N/A' }}</dd>

                    <dt class="col-sm-3">{{ __('Request URL') }}</dt>
                    <dd class="col-sm-9">
                        @if($incident->request_url)
                            <code>{{ $incident->request_url }}</code>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">{{ __('Request ID') }}</dt>
                    <dd class="col-sm-9">
                        @if($incident->request_id)
                            <code>{{ $incident->request_id }}</code>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">{{ __('User') }}</dt>
                    <dd class="col-sm-9">
                        @if($incident->user)
                            {{ $incident->user->name }} ({{ $incident->user->email }})
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </dd>

                    <dt class="col-sm-3">{{ __('Occurrences') }}</dt>
                    <dd class="col-sm-9"><span class="badge bg-secondary">{{ $incident->occurrence_count }}</span></dd>

                    <dt class="col-sm-3">{{ __('First Seen') }}</dt>
                    <dd class="col-sm-9">{{ $incident->first_seen_at?->format('Y-m-d H:i:s') }}</dd>

                    <dt class="col-sm-3">{{ __('Last Seen') }}</dt>
                    <dd class="col-sm-9">{{ $incident->last_seen_at?->format('Y-m-d H:i:s') }}</dd>

                    <dt class="col-sm-3">{{ __('Resolved At') }}</dt>
                    <dd class="col-sm-9">{{ $incident->resolved_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</dd>

                    <dt class="col-sm-3">{{ __('Fingerprint') }}</dt>
                    <dd class="col-sm-9"><code>{{ $incident->fingerprint }}</code></dd>
                </dl>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Message') }}</h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded"><code>{{ $incident->message }}</code></pre>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('Stack Trace') }}</h5>
            </div>
            <div class="card-body">
                @if($incident->stack_trace && config('error_monitoring.show_stack_traces', true))
                    <pre class="bg-light p-3 rounded overflow-auto" style="max-height: 400px;"><code>{{ $incident->stack_trace }}</code></pre>
                @else
                    <div class="text-muted">
                        {{ __('Stack trace is not available or hidden for security reasons.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection