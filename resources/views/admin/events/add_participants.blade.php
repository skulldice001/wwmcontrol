@extends('layouts.admin')

@section('title', __('messages.add_participants_header') . ' - ' . $event->title)

@php
    $translations = [
        "members_count" => __('messages.members_count'),
        "search_players" => __('messages.search_players'),
        "select_all_visible" => __('messages.select_all_visible'),
        "not_available" => __('messages.not_available'),
        "no_participants_found" => __('messages.no_participants_found') ?? "No participants found",
        "add" => __('messages.add'),
        "cancel" => __('messages.cancel')
    ];
@endphp

@section('content')
    <add-participants
        :initial-users='@json($users)'
        title="{{ __('messages.add_participants_header') . ' ' . $event->title }}"
        submit-url="{{ route('admin.events.add_participants', $event->id) }}"
        cancel-url="{{ route('admin.events.index') }}"
        csrf-token="{{ csrf_token() }}"
        :translations='@json($translations)'
    ></add-participants>
@endsection
