@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $membersCount }}</h3>
                <p>Guild Members</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('admin.users.index') }}" class="small-box-footer">
                View members <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $eventsRunning }}</h3>
                <p>Running / Upcoming Events</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <a href="{{ route('admin.events.index') }}" class="small-box-footer">
                View events <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $eventsCompleted }}</h3>
                <p>Completed Events</p>
            </div>
            <div class="icon">
                <i class="fas fa-flag-checkered"></i>
            </div>
            <a href="{{ route('admin.events.index') }}" class="small-box-footer">
                View events <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $roles['Healer'] ?? 0 }} / {{ $roles['Tanker'] ?? 0 }} / {{ $roles['DPS'] ?? 0 }}</h3>
                <p>Members by Role (Healer / Tanker / DPS)</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <a href="{{ route('admin.users.index') }}" class="small-box-footer">
                View members <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>
@endsection
