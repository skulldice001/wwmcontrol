@extends('layouts.admin')

@section('title', __('messages.entertainment_hall'))

@section('content')
<div class="row">
    <div class="col-lg-6 col-6">
        <!-- small box -->
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ __('messages.poker_texas') }}</h3>
                <p>Poker Texas Hold'em</p>
            </div>
            <div class="icon">
                <i class="fas fa-dice"></i>
            </div>
            <a href="{{ route('entertainment.poker') }}" class="small-box-footer">
                {{ __('messages.play_now') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-6 col-6">
        <!-- small box -->
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ __('messages.blackjack') }}</h3>
                <p>{{ __('messages.blackjack_description') }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-chess-king"></i>
            </div>
            <a href="{{ route('entertainment.blackjack') }}" class="small-box-footer">
                {{ __('messages.play_now') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <!-- ./col -->
</div>
@endsection
