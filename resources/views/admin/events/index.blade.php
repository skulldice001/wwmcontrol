@extends('layouts.admin')

@section('title', 'Events Management')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Events List</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createEventModal">
                Create New Event
            </button>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Start Time</th>
                    <th>Created By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                <tr>
                    <td>{{ $event->id }}</td>
                    <td>{{ $event->title }}</td>
                    <td>
                        <span class="badge badge-{{ $event->type == 'guild_war' ? 'danger' : 'success' }}">
                            {{ $event->type == 'guild_war' ? 'Guild War' : 'Casual' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $event->status == 'ongoing' ? 'success' : ($event->status == 'upcoming' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($event->status) }}
                        </span>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($event->start_time)->format('Y-m-d H:i') }}</td>
                    <td>{{ $event->creator ? $event->creator->name : 'N/A' }}</td>
                    <td>
                        <a href="{{ route('admin.events.participants', $event->id) }}" class="btn btn-primary btn-sm mr-1">
                            <i class="fas fa-users"></i> {{ __('messages.participants') ?? 'Participants' }}
                        </a>
                        @if($event->type == 'guild_war')
                            <a href="{{ route('admin.events.formation', $event->id) }}" class="btn btn-warning btn-sm mr-1">
                                <i class="fas fa-users-cog"></i> {{ __('messages.sort_formation') }}
                            </a>
                        @endif
                        <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-info btn-sm mr-1">
                            <i class="fas fa-pencil-alt"></i> Edit
                        </a>
                        <form action="{{ route('admin.events.destroy', $event->id) }}" method="POST" style="display:inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1" role="dialog" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">Select Event Type</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <p>Please select the type of event you want to create:</p>
                <div class="row">
                    <div class="col-6">
                        <a href="{{ route('admin.events.create', ['type' => 'casual']) }}" class="btn btn-success btn-block btn-lg p-4">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i><br>
                            Casual Event
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('admin.events.create', ['type' => 'guild_war']) }}" class="btn btn-danger btn-block btn-lg p-4">
                            <i class="fas fa-khanda fa-2x mb-2"></i><br>
                            Guild War
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
