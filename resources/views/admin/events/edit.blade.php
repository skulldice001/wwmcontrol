@extends('layouts.admin')

@section('title', 'Edit Event')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Edit Event: {{ $event->title }}</h3>
    </div>
    <form action="{{ route('admin.events.update', $event->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $event->title) }}" placeholder="Enter event title">
                @error('title') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Enter description">{{ old('description', $event->description) }}</textarea>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" class="form-control @error('type') is-invalid @enderror">
                    <option value="casual" {{ old('type', $event->type) == 'casual' ? 'selected' : '' }}>Casual</option>
                    <option value="guild_war" {{ old('type', $event->type) == 'guild_war' ? 'selected' : '' }}>Guild War</option>
                </select>
                @error('type') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Start Time</label>
                <input type="datetime-local" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('Y-m-d\TH:i') : '') }}">
                @error('start_time') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>End Time</label>
                <input type="datetime-local" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('Y-m-d\TH:i') : '') }}">
                @error('end_time') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location', $event->location) }}" placeholder="Enter location">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="upcoming" {{ old('status', $event->status) == 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                    <option value="ongoing" {{ old('status', $event->status) == 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                    <option value="completed" {{ old('status', $event->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ old('status', $event->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Update Event</button>
            <a href="{{ route('admin.events.index') }}" class="btn btn-default float-right">Cancel</a>
        </div>
    </form>
</div>
@endsection
