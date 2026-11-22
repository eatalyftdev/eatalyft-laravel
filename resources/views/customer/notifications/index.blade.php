@extends('layouts.app')

@section('title', __('Notifications'))
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h1 class="h3 mb-4">{{ __('Notifications') }}</h1>

        @if(count($notifications) > 0)
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Title') }}</th>
                                <th>{{ __('Message') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                            <tr>
                                <td>{{ $notification->data['title'] ?? __('Notification') }}</span>
                                <td>{{ $notification->data['message'] ?? '' }}</td>
                                <td>{{ $notification->created_at->format('M d, Y \\a\\t H:i') }}</span>
                                <td>
                                    @if(isset($notification->data['url']))
                                    <a href="{{ $notification->data['url'] }}" class="btn btn-sm btn-outline-primary">{{ __('View') }}</a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="ely-empty-icon">
                    <x-eatalyft.image manifestKey="icons.site-notifications" alt="{{ __('No notifications') }}" width="64" height="64" />
                </div>
                <h4>{{ __('No Notifications') }}</h4>
                <p class="text-muted">{{ __('You have no notifications at the moment.') }}</p>
            </div>
        </div>
        @endif
    </div>
@endsection