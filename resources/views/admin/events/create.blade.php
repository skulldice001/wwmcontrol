@extends('layouts.admin')

@section('title', __('messages.create_event_title'))

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.create_event_header') }}</h3>
    </div>

    <create-event-form
        csrf-token="{{ csrf_token() }}"
        route-store="{{ route('admin.events.store') }}"
        route-index="{{ route('admin.events.index') }}"
        :users='@json($users)'
        :old-input='@json(session()->getOldInput() ?? new \stdClass())'
        :errors='@json($errors->all())'
        initial-type="{{ request('type', '') }}"
    ></create-event-form>
</div>
@endsection
