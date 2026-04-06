@extends('layouts.admin')
@section('title', __('messages.lucky_draw_result_btn') . ' — ' . $event->title)

@section('content')
@php $data = $event->lucky_draw_data; @endphp

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-trophy mr-2 text-warning"></i>
            {{ __('messages.lucky_draw_result_btn') }} — {{ $event->title }}
        </h3>
        <div class="card-tools">
            <a href="{{ route('admin.events.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> {{ __('messages.lucky_draw_back') }}
            </a>
        </div>
    </div>
    <div class="card-body">

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-user-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('messages.lucky_draw_reg_deadline') }}</span>
                        <span class="info-box-number">{{ $event->start_time ? \Carbon\Carbon::parse($event->start_time)->format('H:i d/m/Y') : '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('messages.lucky_draw_scheduled_at') }}</span>
                        <span class="info-box-number">{{ $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('H:i d/m/Y') : '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('messages.lucky_draw_drawn_at') }}</span>
                        <span class="info-box-number">
                            {{ $data['drawn_at'] ? \Carbon\Carbon::parse($data['drawn_at'])->format('H:i d/m/Y') : '—' }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('messages.lucky_draw_participants') }}</span>
                        <span class="info-box-number">
                            {{ $event->participants()->count() }} / {{ count($data['winners'] ?? []) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($data['winners']))
            <h5 class="mb-3"><i class="fas fa-medal mr-1 text-warning"></i> {{ __('messages.lucky_draw_winners') }}</h5>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th width="60">{{ __('messages.lucky_draw_rank') }}</th>
                        <th>{{ __('messages.lucky_draw_prize') }}</th>
                        <th>{{ __('messages.lucky_draw_desc') }}</th>
                        <th>{{ __('messages.lucky_draw_zoo_coins') }}</th>
                        <th>{{ __('messages.lucky_draw_winner_name') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['winners'] as $w)
                    <tr class="{{ $w['rank'] === 1 ? 'table-warning' : '' }}">
                        <td class="text-center font-weight-bold">
                            @if($w['rank'] === 1) 🥇
                            @elseif($w['rank'] === 2) 🥈
                            @elseif($w['rank'] === 3) 🥉
                            @else {{ $w['rank'] }}
                            @endif
                        </td>
                        <td class="font-weight-bold">{{ $w['prize_name'] }}</td>
                        <td class="text-muted">{{ $w['prize_desc'] ?? '—' }}</td>
                        <td>
                            @if($w['zoo_coin_amount'] > 0)
                                <span class="badge badge-warning">🪙 {{ number_format($w['zoo_coin_amount']) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $w['ingame_name'] }}</strong>
                            <br><small class="text-muted">{{ $w['username'] }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                {{ __('messages.lucky_draw_no_winners') }}
            </div>
        @endif

        @if(!empty($data['prizes']) && empty($data['drawn_at']))
            <hr>
            <h5>{{ __('messages.lucky_draw_prizes_setup') }}</h5>
            <ul>
                @foreach($data['prizes'] as $i => $p)
                    <li>
                        <strong>{{ __('messages.lucky_draw_prize_label') }} {{ $i + 1 }}: {{ $p['name'] }}</strong>
                        @if(!empty($p['description'])) — {{ $p['description'] }} @endif
                        @if(!empty($p['zoo_coin_amount'])) · 🪙 {{ number_format($p['zoo_coin_amount']) }} @endif
                    </li>
                @endforeach
            </ul>

            <form action="{{ route('admin.events.run_draw', $event->id) }}" method="POST"
                  onsubmit="return confirm('{{ __('messages.lucky_draw_confirm') }}')">
                @csrf
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-dice mr-1"></i> {{ __('messages.lucky_draw_run_manual') }}
                </button>
            </form>
        @endif

    </div>
</div>
@endsection
