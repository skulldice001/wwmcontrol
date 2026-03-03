@extends('layouts.admin')

@section('title', __('messages.library'))

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-info">
            <div class="inner">
                <h4>{{ __('messages.library_sections.character_development') }}</h4>
                <p>&nbsp;</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-graduate"></i>
            </div>
            <a href="#" class="small-box-footer">{{ __('messages.view_details') }} <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-success">
            <div class="inner">
                <h4>{{ __('messages.library_sections.arena_summary') }}</h4>
                <p>&nbsp;</p>
            </div>
            <div class="icon">
                <i class="fas fa-trophy"></i>
            </div>
            <a href="#" class="small-box-footer">{{ __('messages.view_details') }} <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-warning">
            <div class="inner">
                <h4>{{ __('messages.library_sections.guild_war_experience') }}</h4>
                <p>&nbsp;</p>
            </div>
            <div class="icon">
                <i class="fas fa-fist-raised"></i>
            </div>
            <a href="#" class="small-box-footer">{{ __('messages.view_details') ?? 'View Details' }} <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-danger">
            <div class="inner">
                <h4>{{ __('messages.library_sections.dungeon_summary') }}</h4>
                <p>&nbsp;</p>
            </div>
            <div class="icon">
                <i class="fas fa-dungeon"></i>
            </div>
            <a href="#" class="small-box-footer">{{ __('messages.view_details') ?? 'View Details' }} <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <!-- ./col -->
</div>
@endsection
