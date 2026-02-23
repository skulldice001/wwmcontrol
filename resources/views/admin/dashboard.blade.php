@extends('layouts.admin')

@section('title', __('messages.admin_dashboard_title'))

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $membersCount }}</h3>
                <p>{{ __('messages.dashboard_guild_members') }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('admin.users.index') }}" class="small-box-footer">
                {{ __('messages.dashboard_view_members') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $eventsRunning }}</h3>
                <p>{{ __('messages.dashboard_running_events') }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <a href="{{ route('admin.events.index') }}" class="small-box-footer">
                {{ __('messages.dashboard_view_events') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $eventsCompleted }}</h3>
                <p>{{ __('messages.dashboard_completed_events') }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-flag-checkered"></i>
            </div>
            <a href="{{ route('admin.events.index') }}" class="small-box-footer">
                {{ __('messages.dashboard_view_events') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $roles['Healer'] ?? 0 }} / {{ $roles['Tanker'] ?? 0 }} / {{ $roles['DPS'] ?? 0 }}</h3>
                <p>{{ __('messages.dashboard_members_by_role') }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <a href="{{ route('admin.users.index') }}" class="small-box-footer">
                {{ __('messages.dashboard_view_members') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>
@endsection
