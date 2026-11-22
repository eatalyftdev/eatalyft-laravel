@extends('layouts.app')

@section('title', __('System Settings'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ __('System Settings') }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form id="settingsForm">
            @foreach($groups as $groupKey => $settings)
                <div class="mb-4">
                    <h5 class="text-uppercase text-muted mb-3">{{ ucfirst($groupKey) }}</h5>
                    @foreach($settings as $key => $setting)
                        <div class="row mb-3">
                            <label class="col-sm-2 col-form-label">{{ $setting['label'] ?? $key }}</label>
                            <div class="col-sm-10">
                                @if($setting['type'] === 'bool')
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="{{ $key }}" id="{{ $key }}" {{ $setting['value'] ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $key }}"></label>
                                    </div>
                                @elseif($setting['type'] === 'array')
                                    <textarea class="form-control" name="{{ $key }}" rows="3">{{ json_encode($setting['value'], JSON_PRETTY_PRINT) }}</textarea>
                                @else
                                    <input type="text" class="form-control" name="{{ $key }}" value="{{ $setting['value'] }}">
                                @endif
                                @if($setting['description'])
                                    <div class="form-text">{{ $setting['description'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <div class="row">
                <div class="col-sm-10 offset-sm-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection