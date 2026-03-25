@extends('layouts.admin')

@section('title', $table->name)

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('success') }}
</div>
@endif

<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('entertainment.poker') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.poker_lobby') }}
        </a>

        {{-- Leave Table button --}}
        <form action="{{ route('entertainment.poker.leave', $table) }}" method="POST" class="d-inline float-right">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"
                onclick="return confirm('{{ __('messages.leave_table') }}?')">
                <i class="fas fa-sign-out-alt"></i> {{ __('messages.leave_table') }}
            </button>
        </form>
    </div>
</div>

<div class="row">
    {{-- Table info card --}}
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table"></i> {{ $table->name }}
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">{{ __('messages.table_type') }}</td>
                        <td><strong>{{ __('messages.' . $table->type) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('messages.blinds') }}</td>
                        <td>
                            <span class="badge badge-info">
                                {{ number_format($table->small_blind) }} / {{ number_format($table->big_blind) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('messages.buy_in') }}</td>
                        <td>{{ number_format($table->min_buy_in) }} – {{ number_format($table->max_buy_in) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('messages.players') }}</td>
                        <td>
                            <span id="room-player-count">{{ $table->current_players }}</span> / {{ $table->max_players }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">{{ __('messages.status') }}</td>
                        <td>
                            <span id="room-status-badge">
                                @if($table->status == 'waiting')
                                    <span class="badge badge-success">{{ __('messages.waiting') }}</span>
                                @elseif($table->status == 'full')
                                    <span class="badge badge-danger">{{ __('messages.table_full') }}</span>
                                @else
                                    <span class="badge badge-warning">{{ __('messages.playing') }}</span>
                                @endif
                            </span>
                        </td>
                    </tr>
                </table>

                {{-- Player count progress bar --}}
                <div class="progress mt-2">
                    <div id="room-progress-bar"
                         class="progress-bar bg-green"
                         role="progressbar"
                         style="width: {{ ($table->current_players / $table->max_players) * 100 }}%"
                         aria-valuenow="{{ $table->current_players }}"
                         aria-valuemin="0"
                         aria-valuemax="{{ $table->max_players }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Game area placeholder --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gamepad"></i> Game Area</h3>
            </div>
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-cards fa-3x mb-3"></i>
                <p>Game coming soon...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script type="module">
    $(document).ready(function () {
        const tableId = {{ $table->id }};
        const maxPlayers = {{ $table->max_players }};

        // Initialize WebSocket — we are inside a game room
        if (typeof window.initEcho === 'function') {
            window.initEcho();
        }

        if (window.Echo) {
            window.Echo.channel('poker.lobby')
                .listen('PokerTableUpdated', (e) => {
                    if (e.table.id !== tableId) return;

                    // Update player count and progress bar
                    const count = e.table.current_players;
                    $('#room-player-count').text(count);
                    const pct = (count / maxPlayers) * 100;
                    $('#room-progress-bar').css('width', pct + '%').attr('aria-valuenow', count);

                    // Update status badge
                    let badge = '';
                    if (e.table.status === 'waiting') {
                        badge = '<span class="badge badge-success">{{ __("messages.waiting") }}</span>';
                    } else if (e.table.status === 'full') {
                        badge = '<span class="badge badge-danger">{{ __("messages.table_full") }}</span>';
                    } else {
                        badge = '<span class="badge badge-warning">{{ __("messages.playing") }}</span>';
                    }
                    $('#room-status-badge').html(badge);
                });
        }
    });
</script>
@endpush
