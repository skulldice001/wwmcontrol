@extends('layouts.admin')

@section('title', __('messages.create_event_title'))

@section('content')
@php
    $translations = [
        "title_label" => __("messages.event_field_title"),
        "title_placeholder" => __("messages.event_placeholder_title"),
        "description_label" => __("messages.event_field_description"),
        "description_placeholder" => __("messages.event_placeholder_description"),
        "type_label" => __("messages.event_field_type"),
        "type_casual" => __("messages.event_type_casual"),
        "type_guild_war" => __("messages.event_type_guild_war"),
        "type_lucky_draw" => __("messages.event_type_lucky_draw"),
        "start_time_label" => __("messages.event_field_start_time"),
        "end_time_label" => __("messages.event_field_end_time"),
        "location_label" => __("messages.event_field_location"),
        "location_placeholder" => __("messages.event_placeholder_location"),
        "status_label" => __("messages.event_field_status"),
        "status_upcoming" => __("messages.event_status_upcoming"),
        "status_ongoing" => __("messages.event_status_ongoing"),
        "status_completed" => __("messages.event_status_completed"),
        "status_cancelled" => __("messages.event_status_cancelled"),
        "create_btn" => __("messages.create_event_btn"),
        "cancel_btn" => __("messages.cancel"),
        "guild_war_title_format" => __("messages.guild_war_title_format"),
        "guild_war_title_format_same_month" => __("messages.guild_war_title_format_same_month"),
        "ld_reg_label"     => __("messages.lucky_draw_reg_label"),
        "ld_reg_hint"      => __("messages.lucky_draw_reg_hint"),
        "ld_time_label"    => __("messages.lucky_draw_time_label"),
        "ld_time_hint"     => __("messages.lucky_draw_time_hint"),
        "ld_prizes_title"  => __("messages.lucky_draw_prizes_title"),
        "ld_add_prize"     => __("messages.lucky_draw_add_prize"),
        "ld_multi_hint"    => __("messages.lucky_draw_multi_hint"),
        "ld_prize_badge"   => __("messages.lucky_draw_prize_badge"),
        "ld_prize_name_ph" => __("messages.lucky_draw_prize_name_ph"),
        "ld_prize_desc_ph" => __("messages.lucky_draw_prize_desc_ph"),
    ];
@endphp
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
        :translations='@json($translations)'
    ></create-event-form>
</div>
@endsection
