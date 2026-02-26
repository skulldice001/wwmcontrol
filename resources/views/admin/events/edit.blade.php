@extends('layouts.admin')

@section('title', __('messages.edit_event_title'))

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.edit_event_header') }} {{ $event->title }}</h3>
    </div>
    <form action="{{ route('admin.events.update', $event->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label>{{ __('messages.discord_id') }}</label>
                <input type="text" name="discord_id" class="form-control" value="{{ old('discord_id', $event->discord_id) }}" placeholder="Discord Message ID">
            </div>
            <div class="form-group">
                <label>{{ __('messages.event_field_title') }}</label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $event->title) }}" placeholder="{{ __('messages.event_placeholder_title') }}">
                @error('title') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.event_field_description') }}</label>
                <textarea name="description" class="form-control" rows="3" placeholder="{{ __('messages.event_placeholder_description') }}">{{ old('description', $event->description) }}</textarea>
            </div>
            <div class="form-group">
                <label>{{ __('messages.event_field_type') }}</label>
                <select name="type" class="form-control @error('type') is-invalid @enderror">
                    <option value="casual" {{ old('type', $event->type) == 'casual' ? 'selected' : '' }}>{{ __('messages.event_type_casual') }}</option>
                    <option value="guild_war" {{ old('type', $event->type) == 'guild_war' ? 'selected' : '' }}>{{ __('messages.event_type_guild_war') }}</option>
                </select>
                @error('type') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.event_field_start_time') }}</label>
                <input type="datetime-local" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('Y-m-d\TH:i') : '') }}">
                @error('start_time') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.event_field_end_time') }}</label>
                <input type="datetime-local" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time', $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('Y-m-d\TH:i') : '') }}">
                @error('end_time') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.event_field_location') }}</label>
                <input type="text" name="location" class="form-control" value="{{ old('location', $event->location) }}" placeholder="{{ __('messages.event_placeholder_location') }}">
            </div>
            <div class="form-group">
                <label>{{ __('messages.event_field_status') }}</label>
                <select name="status" class="form-control">
                    <option value="upcoming" {{ old('status', $event->status) == 'upcoming' ? 'selected' : '' }}>{{ __('messages.event_status_upcoming') }}</option>
                    <option value="ongoing" {{ old('status', $event->status) == 'ongoing' ? 'selected' : '' }}>{{ __('messages.event_status_ongoing') }}</option>
                    <option value="completed" {{ old('status', $event->status) == 'completed' ? 'selected' : '' }}>{{ __('messages.event_status_completed') }}</option>
                    <option value="cancelled" {{ old('status', $event->status) == 'cancelled' ? 'selected' : '' }}>{{ __('messages.event_status_cancelled') }}</option>
                </select>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('messages.update_event') }}</button>
            <a href="{{ route('admin.events.index') }}" class="btn btn-default float-right">{{ __('messages.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
