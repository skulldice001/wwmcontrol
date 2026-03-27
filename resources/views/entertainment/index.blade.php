@extends('layouts.admin')

@section('title', __('messages.entertainment_hall'))

@section('content')
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ __('messages.poker_texas') }}</h3>
                <p>
                    {{ __('messages.tx_rooms_total') }}: <strong>{{ $stats['poker']['total'] }}</strong> &nbsp;|&nbsp;
                    {{ __('messages.tx_rooms_playing') }}: <strong>{{ $stats['poker']['playing'] }}</strong> &nbsp;|&nbsp;
                    {{ __('messages.tx_rooms_waiting') }}: <strong>{{ $stats['poker']['waiting'] }}</strong>
                </p>
            </div>
            <div class="icon">
                <i class="fas fa-dice"></i>
            </div>
            <a href="{{ route('entertainment.poker') }}" class="small-box-footer">
                {{ __('messages.play_now') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ __('messages.blackjack') }}</h3>
                <p>
                    {{ __('messages.tx_rooms_total') }}: <strong>{{ $stats['blackjack']['total'] }}</strong> &nbsp;|&nbsp;
                    {{ __('messages.tx_rooms_playing') }}: <strong>{{ $stats['blackjack']['playing'] }}</strong> &nbsp;|&nbsp;
                    {{ __('messages.tx_rooms_waiting') }}: <strong>{{ $stats['blackjack']['waiting'] }}</strong>
                </p>
            </div>
            <div class="icon">
                <i class="fas fa-chess-king"></i>
            </div>
            <a href="{{ route('entertainment.blackjack') }}" class="small-box-footer">
                {{ __('messages.play_now') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ __('messages.taixiu') }}</h3>
                <p>
                    {{ __('messages.tx_rooms_total') }}: <strong>{{ $stats['taixiu']['total'] }}</strong> &nbsp;|&nbsp;
                    {{ __('messages.tx_rooms_playing') }}: <strong>{{ $stats['taixiu']['playing'] }}</strong> &nbsp;|&nbsp;
                    {{ __('messages.tx_rooms_waiting') }}: <strong>{{ $stats['taixiu']['waiting'] }}</strong>
                </p>
            </div>
            <div class="icon">
                <i class="fas fa-dice-d6"></i>
            </div>
            <a href="{{ route('entertainment.taixiu') }}" class="small-box-footer">
                {{ __('messages.play_now') }} <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>
@endsection
