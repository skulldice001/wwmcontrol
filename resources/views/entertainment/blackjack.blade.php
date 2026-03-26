@extends('layouts.admin')

@section('title', __('messages.blackjack'))

@push('styles')
<style>
.bj-table-card {
    background: #1a2332;
    border: 1px solid #2d3f55;
    border-radius: 12px;
    transition: border-color .2s, box-shadow .2s;
}
.bj-table-card:hover {
    border-color: #27ae60;
    box-shadow: 0 0 16px rgba(39,174,96,.25);
}
.bj-felt-badge {
    display: inline-block;
    background: radial-gradient(ellipse at center, #1e7a3c 0%, #145d2e 100%);
    border-radius: 50px;
    padding: 4px 14px;
    font-size: 12px;
    color: #a8e6bc;
    letter-spacing: 1px;
    text-transform: uppercase;
}
.bj-bet-range {
    font-size: 13px;
    color: #f6c23e;
}
</style>
@endpush

@section('content')
<div class="d-flex align-items-center mb-3">
    <a href="{{ route('entertainment.index') }}" class="btn btn-sm btn-secondary mr-2">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h4 class="mb-0">{{ __('messages.bj_lobby') }}</h4>
</div>

<div class="row">
    @forelse($tables as $table)
    <div class="col-md-4 mb-3">
        <div class="bj-table-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="font-weight-bold" style="font-size:16px; color:#e0e0e0;">{{ $table->name }}</span>
                <span class="bj-felt-badge">Blackjack</span>
            </div>

            <div class="bj-bet-range mb-3">
                <i class="fas fa-coins mr-1" style="color:#f6c23e;"></i>
                {{ __('messages.bj_bet_range') }}:
                <strong>{{ number_format($table->min_bet) }}</strong>
                –
                <strong>{{ number_format($table->max_bet) }}</strong> Z
            </div>

            <a href="{{ route('entertainment.blackjack.show', $table) }}"
               class="btn btn-success btn-block btn-sm">
                <i class="fas fa-play mr-1"></i> {{ __('messages.bj_play') }}
            </a>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-secondary">{{ __('messages.coming_soon') }}</div>
    </div>
    @endforelse
</div>
@endsection
