@extends('layouts.admin')

@section('title', $table->name)

@push('styles')
<style>
/* ── Lobby ───────────────────────────────────────────────────── */
.lobby-card {
    background: rgba(0,0,0,.55);
    border: 1px solid rgba(255,255,255,.15);
    color: #ddd;
    border-radius: 12px;
    overflow: hidden;
}
.lobby-card .card-header {
    background: rgba(0,0,0,.3);
    border-color: rgba(255,255,255,.1);
}
.lobby-card .card-footer {
    background: rgba(0,0,0,.3);
    border-color: rgba(255,255,255,.1);
    text-align: center;
}
.lobby-player-item {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.lobby-player-item:last-child { border-bottom: none; }
.ready-dot {
    width: 12px; height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-right: 10px;
    transition: background .3s, box-shadow .3s;
}
.ready-dot.is-ready  { background: #2ecc71; box-shadow: 0 0 6px #2ecc71aa; }
.ready-dot.not-ready { background: #555; }

/* ── Poker Room Layout ───────────────────────────────────────── */
.poker-room { display: flex; gap: 16px; height: calc(100vh - 160px); min-height: 580px; }
.table-col   { flex: 1; display: flex; flex-direction: column; gap: 12px; min-width: 0; }
.side-col    { width: 220px; flex-shrink: 0; display: flex; flex-direction: column; gap: 10px; }

/* ── Felt Table ──────────────────────────────────────────────── */
.table-wrap  { position: relative; flex: 1; }
.felt        {
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 50% 45%, #2a7a3a 0%, #1a5828 55%, #0d3d1a 100%);
    border-radius: 50%;
    border: 14px solid #5c3310;
    box-shadow: 0 0 0 3px #8b5e2a, 0 8px 40px rgba(0,0,0,.7), inset 0 0 60px rgba(0,0,0,.25);
}

/* ── Player Seats ────────────────────────────────────────────── */
.seat {
    position: absolute;
    text-align: center;
    transform: translateX(-50%);
    z-index: 10;
    transition: opacity .3s;
}
/* 6 positions around the oval (percentages = left / top of .table-wrap) */
.seat-0 { left: 50%;  bottom: -90px; }
.seat-1 { left: 80%;  bottom:   6%;  }
.seat-2 { left: 93%;  top:     28%;  }
.seat-3 { left: 67%;  top:    -72px; }
.seat-4 { left: 33%;  top:    -72px; }
.seat-5 { left:  7%;  top:     28%;  }

.seat.folded   { opacity: .45; }
.seat.active-turn .seat-box { box-shadow: 0 0 0 3px #ffe34d, 0 0 18px #ffe34d88; }

.seat-box {
    background: rgba(0,0,0,.72);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 10px;
    padding: 6px 10px;
    min-width: 110px;
    color: #fff;
    font-size: 12px;
    transition: box-shadow .3s;
}
.seat-name  { font-weight: 700; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 110px; }
.seat-chips { color: #ffd700; font-size: 11px; }
.seat-bet   { color: #7ee8a2; font-size: 11px; min-height: 14px; }
.seat-cards { display: flex; justify-content: center; gap: 3px; margin-top: 5px; }

.badge-pos {
    font-size: 9px; padding: 1px 4px;
    border-radius: 3px; font-weight: 800; margin-right: 2px;
}
.badge-d  { background: #fff; color: #000; }
.badge-sb { background: #3498db; color: #fff; }
.badge-bb { background: #e74c3c; color: #fff; }

/* ── Playing Cards (CSS-rendered, no images needed) ─────────── */
.play-card {
    width: 46px; height: 64px;
    border-radius: 5px;
    background: #fff;
    border: 1px solid #bbb;
    box-shadow: 1px 2px 4px rgba(0,0,0,.45);
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    user-select: none;
    vertical-align: middle;
}
.play-card.red   { color: #c0392b; }
.play-card.black { color: #1a1a1a; }
.play-card .cc-top {
    position: absolute; top: 3px; left: 4px;
    font-size: 10px; line-height: 1.1;
    font-weight: 800; text-align: center;
}
.play-card .cc-bot {
    position: absolute; bottom: 3px; right: 4px;
    font-size: 10px; line-height: 1.1;
    font-weight: 800; text-align: center;
    transform: rotate(180deg);
}
.play-card .cc-mid { font-size: 20px; line-height: 1; }
.play-card.winner-glow { box-shadow: 0 0 10px 3px #ffe34d; border-color: #ffe34d; }
.card-back {
    width: 46px; height: 64px;
    border-radius: 5px;
    background: repeating-linear-gradient(
        135deg,
        #1a3a8c 0px, #1a3a8c 4px,
        #0d2660 4px, #0d2660 8px
    );
    border: 2px solid #4a6fbf;
    box-shadow: 1px 2px 4px rgba(0,0,0,.5);
    flex-shrink: 0;
}

/* ── Community Cards ─────────────────────────────────────────── */
.community-area {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -55%);
    text-align: center;
}
.community-cards { display: flex; gap: 6px; justify-content: center; align-items: flex-end; }
.card-placeholder {
    width: 46px; height: 64px;
    border: 2px dashed rgba(255,255,255,.2);
    border-radius: 4px;
}
.pot-display {
    margin-top: 8px;
    color: #ffe34d;
    font-size: 15px;
    font-weight: 700;
    text-shadow: 0 1px 4px rgba(0,0,0,.8);
}
.phase-badge {
    display: inline-block;
    background: rgba(0,0,0,.55);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 20px;
    padding: 2px 12px;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 6px;
}

/* ── Human cards (larger, at bottom) ────────────────────────── */
.my-cards { display: flex; gap: 8px; justify-content: center; }
.my-cards .play-card      { width: 70px; height: 98px; }
.my-cards .play-card .cc-top,
.my-cards .play-card .cc-bot { font-size: 14px; }
.my-cards .play-card .cc-mid { font-size: 32px; }
.my-cards .card-back      { width: 70px; height: 98px; }

/* ── Action Panel ────────────────────────────────────────────── */
.action-panel {
    background: rgba(0,0,0,.55);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 12px;
    padding: 14px 18px;
}
.action-panel .btn { min-width: 90px; font-weight: 700; }
.raise-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
.raise-row input[type=range] { flex: 1; min-width: 120px; }
.raise-row input[type=number] { width: 90px; }

/* ── Side Panel ──────────────────────────────────────────────── */
.side-card {
    background: rgba(0,0,0,.5);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 10px;
    padding: 12px;
    color: #ddd;
    font-size: 12px;
}
.side-card h6 { color: #fff; font-size: 13px; margin-bottom: 8px; font-weight: 700; }
.log-entry { border-bottom: 1px solid rgba(255,255,255,.07); padding: 3px 0; }
.log-entry:last-child { border-bottom: none; }

/* ── Winner banner ───────────────────────────────────────────── */
#winner-banner {
    display: none;
    position: absolute; inset: 0;
    background: rgba(0,0,0,.6);
    border-radius: 50%;
    align-items: center; justify-content: center;
    z-index: 20;
    flex-direction: column;
    text-align: center;
    padding: 20px;
}
#winner-banner h4 { color: #ffe34d; font-size: 22px; text-shadow: 0 2px 8px rgba(0,0,0,.9); }
#winner-banner p  { color: #fff; }

/* ── Turn timer ──────────────────────────────────────────────── */
#turn-timer { margin-bottom: 8px; }
#turn-timer .progress { height: 6px; border-radius: 3px; background: rgba(255,255,255,.15); }
#timer-text { font-size: 12px; font-weight: 700; color: #ffe34d; margin-top: 3px; }

/* ── Thinking indicator ──────────────────────────────────────── */
.thinking { font-size: 10px; color: #aaa; animation: blink 1s step-start infinite; }
@keyframes blink { 50% { opacity: 0; } }

/* ── Responsive tweaks ───────────────────────────────────────── */
@media (max-width: 768px) {
    .poker-room { flex-direction: column; height: auto; }
    .side-col   { width: 100%; }
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="row mb-2">
    <div class="col-12 d-flex align-items-center justify-content-between">
        <a href="{{ route('entertainment.poker') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.poker_lobby') }}
        </a>
        <span class="text-muted small">
            {{ $table->name }} &mdash; {{ __('messages.blinds') }}: {{ number_format($table->small_blind) }}/{{ number_format($table->big_blind) }}
        </span>
        <form action="{{ route('entertainment.poker.leave', $table) }}" method="POST" class="d-inline" id="leave-table-form">
            @csrf @method('DELETE')
            <button type="button" class="btn btn-sm btn-danger" id="leave-table-btn">
                <i class="fas fa-sign-out-alt"></i> {{ __('messages.leave_table') }}
            </button>
        </form>
    </div>
</div>

{{-- ── Lobby Section (pre-game waiting room) ──────────────────── --}}
<div id="lobby-section">
    <div class="row justify-content-center mt-3">
        <div class="col-md-5 col-lg-4">
            <div class="lobby-card card">
                <div class="card-header">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-users mr-2"></i>{{ $table->name }}
                    </h5>
                    <small class="text-muted">
                        {{ __('messages.blinds') }}: {{ number_format($table->small_blind) }}/{{ number_format($table->big_blind) }}
                        &nbsp;&middot;&nbsp;
                        {{ __('messages.buy_in') }}: {{ number_format($table->max_buy_in) }}
                    </small>
                </div>
                <div id="lobby-players" class="card-body p-0">
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
                        {{ __('messages.poker_loading_players') }}
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <i class="fas fa-coins" style="color:#f6c23e;"></i>
                            Buy-in: <strong style="color:#f6c23e;">{{ number_format($table->max_buy_in) }} Z</strong> / ván
                        </small>
                        <small class="text-muted">
                            Số dư: <strong style="color:#f6c23e;" id="lobby-zcoin">{{ number_format(Auth::user()->z_coins) }} Z</strong>
                        </small>
                    </div>
                    <div id="lobby-msg" class="text-muted small mb-2"></div>
                    <button id="ready-btn" class="btn btn-lg btn-success px-5">
                        <i class="fas fa-check mr-1"></i> {{ __('messages.poker_im_ready') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Game Section (shown after game starts) ─────────────────── --}}
<div id="game-section" style="display:none">
<div class="poker-room">

    {{-- Main Table Column --}}
    <div class="table-col">

        {{-- Felt table --}}
        <div class="table-wrap" id="table-wrap">
            <div class="felt" id="felt">

                {{-- Community cards + pot in center --}}
                <div class="community-area">
                    <div class="phase-badge" id="phase-label">WAITING</div>
                    <div class="community-cards" id="community-cards">
                        @for($i = 0; $i < 5; $i++)
                            <div class="card-placeholder" id="cc-slot-{{ $i }}"></div>
                        @endfor
                    </div>
                    <div class="pot-display" id="pot-display"></div>
                </div>

                {{-- Winner overlay --}}
                <div id="winner-banner">
                    <h4 id="winner-title"></h4>
                    <p id="winner-hand"></p>
                </div>

                {{-- AI Seats 1-5 --}}
                @for($s = 1; $s <= 5; $s++)
                    <div class="seat seat-{{ $s }}" id="seat-{{ $s }}" style="display:none">
                        <div class="seat-box" id="seat-box-{{ $s }}">
                            <div class="seat-name"  id="seat-name-{{ $s }}">—</div>
                            <div class="seat-chips" id="seat-chips-{{ $s }}"></div>
                            <div class="seat-bet"   id="seat-bet-{{ $s }}"></div>
                            <div class="seat-cards" id="seat-cards-{{ $s }}"></div>
                        </div>
                    </div>
                @endfor

            </div>{{-- /felt --}}
        </div>{{-- /table-wrap --}}

        {{-- Human seat (seat-0, below table) --}}
        <div class="seat seat-0" id="seat-0" style="position:relative; transform:none; left:auto; bottom:auto;">
            <div class="seat-box" id="seat-box-0">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="seat-name" id="seat-name-0">You</span>
                    <span id="seat-pos-0"></span>
                </div>
                <div class="seat-chips" id="seat-chips-0"></div>
                <div class="seat-bet"   id="seat-bet-0"></div>
                <div class="my-cards"   id="seat-cards-0"></div>
            </div>
        </div>

        {{-- Action buttons --}}
        <div class="action-panel" id="action-panel">
            {{-- Turn countdown timer --}}
            <div id="turn-timer" style="display:none;">
                <div class="progress">
                    <div id="timer-bar" class="progress-bar bg-warning" role="progressbar" style="width:100%; transition:none;"></div>
                </div>
                <div class="text-center" id="timer-text">60s</div>
            </div>
            <div id="action-btns" style="display:none">
                <div class="d-flex gap-2 flex-wrap justify-content-center">
                    <button class="btn btn-danger"  id="btn-fold"  onclick="sendAction('fold')">
                        <i class="fas fa-times"></i> Fold
                    </button>
                    <button class="btn btn-secondary" id="btn-check" onclick="sendAction('check')" style="display:none">
                        <i class="fas fa-hand-paper"></i> Check
                    </button>
                    <button class="btn btn-warning"  id="btn-call"  onclick="sendAction('call')" style="display:none">
                        <i class="fas fa-hand-holding-usd"></i> Call <span id="call-amount"></span>
                    </button>
                    <button class="btn btn-primary"  id="btn-raise" onclick="doRaise()">
                        <i class="fas fa-chevron-up"></i> Raise
                    </button>
                    <button class="btn btn-outline-warning" id="btn-allin" onclick="sendAction('allin')">
                        All-in
                    </button>
                </div>
                <div class="raise-row" id="raise-row" style="display:none">
                    <span class="text-white small">Raise to:</span>
                    <input type="range"  id="raise-slider" min="0" max="10000" step="100" oninput="syncRaise(this.value)">
                    <input type="number" id="raise-input"  min="0" step="100"  oninput="syncRaise(this.value)" class="form-control form-control-sm" style="width:100px">
                    <button class="btn btn-sm btn-primary" onclick="confirmRaise()">OK</button>
                    <button class="btn btn-sm btn-secondary" onclick="hideRaiseRow()">X</button>
                </div>
            </div>
            <div id="opponent-turn" class="text-center text-muted small py-2" style="display:none">
                <span class="thinking">Opponent thinking...</span>
            </div>
            <div id="showdown-msg" class="text-center py-2" style="display:none">
                <button class="btn btn-success" id="new-hand-btn" onclick="dealHand()">
                    <i class="fas fa-redo"></i> New Hand
                </button>
            </div>
        </div>

    </div>{{-- /table-col --}}

    {{-- Side Panel --}}
    <div class="side-col">
        <div class="side-card">
            <h6><i class="fas fa-info-circle mr-1"></i> Table</h6>
            <div>{{ $table->name }}</div>
            <div class="text-muted">{{ __('messages.blinds') }}: {{ number_format($table->small_blind) }}/{{ number_format($table->big_blind) }}</div>
            <div class="text-muted">{{ __('messages.buy_in') }}: {{ number_format($table->min_buy_in) }}-{{ number_format($table->max_buy_in) }}</div>
        </div>
        <div class="side-card" style="flex:1; overflow-y:auto;">
            <h6><i class="fas fa-scroll mr-1"></i> Log</h6>
            <div id="action-log"></div>
        </div>
        <div class="side-card">
            <h6><i class="fas fa-coins mr-1" style="color:#f6c23e;"></i> Z-Coin</h6>
            <div id="zcoin-balance" style="font-size:18px; font-weight:800; color:#f6c23e; letter-spacing:.5px;">—</div>
            <div class="text-muted" style="font-size:11px;">Buy-in: {{ number_format($table->max_buy_in) }} Z / ván</div>
        </div>
        <div class="side-card">
            <h6><i class="fas fa-trophy mr-1"></i> Hand Rankings</h6>
            <div style="font-size:11px; line-height:1.8; color:#bbb;">
                <div>Royal Flush</div>
                <div>Straight Flush</div>
                <div>4 of a Kind</div>
                <div>Full House</div>
                <div>Flush</div>
                <div>Straight</div>
                <div>3 of a Kind</div>
                <div>Two Pair</div>
                <div>One Pair</div>
                <div>High Card</div>
            </div>
        </div>
    </div>

</div>{{-- /poker-room --}}
</div>{{-- /game-section --}}

@endsection

@include('partials.notify')

@push('scripts')
<script type="module">
const MSG = {
    confirmLeave   : '{{ __("messages.poker_confirm_leave") }}',
    connectionError: '{{ __("messages.poker_connection_error") }}',
    cantStartHand  : '{{ __("messages.poker_cant_start_hand") }}',
    imReady        : '{{ __("messages.poker_im_ready") }}',
    cancelReady    : '{{ __("messages.poker_cancel_ready") }}',
    playersReady   : '{{ __("messages.poker_players_ready") }}',
};

const CSRF      = $('meta[name="csrf-token"]').attr('content');
const myUserId  = {{ Auth::id() }};
const tableId   = {{ $table->id }};
const readyUrl  = "{{ route('entertainment.poker.ready',      $table) }}";
const startUrl  = "{{ route('entertainment.poker.game.start', $table) }}";
const stateUrl  = "{{ route('entertainment.poker.game.state', $table) }}";
const actionUrl = "{{ route('entertainment.poker.game.action',$table) }}";

let gameState    = null;
let pollTimer    = null;
let pollXhr      = null;
let turnInterval = null;

$(document).ready(function () {
    loadState();

    if (typeof window.initEcho === 'function') window.initEcho();
    if (window.Echo) {
        window.Echo.channel(`poker.room.${tableId}`)
            .listen('PokerRoomUpdated', onRoomEvent);
    }

    $('#leave-table-btn').on('click', function () {
        confirmDialog(MSG.confirmLeave, function () {
            $('#leave-table-form').submit();
        });
    });

    window.addEventListener('beforeunload', function () {
        if (window.Echo) window.Echo.leave(`poker.room.${tableId}`);
        stopPoll();
        if (turnInterval) clearInterval(turnInterval);
    });
});

function onRoomEvent(e) {
    if (e.type === 'ready_update') {
        updateLobby(e.players);
    } else if (e.type === 'game_started') {
        const myState = e.seats && e.seats[myUserId];
        if (myState) { showGameSection(); render(myState); }
    } else if (e.type === 'game_update') {
        const myState = e.seats && e.seats[myUserId];
        if (myState) render(myState);
    } else if (e.type === 'refresh') {
        // Server auto-folded someone — re-fetch full state
        loadState();
    }
}

// ── State Loading ─────────────────────────────────────────────────────────
function loadState() {
    $.get(stateUrl).done(function (res) {
        if (res.state) {
            showGameSection();
            render(res.state);
        } else {
            showLobbySection();
            if (res.players) updateLobby(res.players);
        }
    });
}

// ── Lobby ─────────────────────────────────────────────────────────────────
function showLobbySection() {
    $('#lobby-section').show();
    $('#game-section').hide();
    stopPoll();
}

function showGameSection() {
    $('#lobby-section').hide();
    $('#game-section').show();
}

function updateLobby(players) {
    const $list    = $('#lobby-players').empty();
    let readyCount = 0;
    players.forEach(function (p) {
        if (p.is_ready) readyCount++;
        const dotCls = p.is_ready ? 'is-ready' : 'not-ready';
        const badge  = p.is_ready
            ? '<span class="badge badge-success ml-auto">Ready</span>'
            : '<span class="badge badge-secondary ml-auto">Waiting</span>';
        $list.append(
            `<div class="lobby-player-item">
                <span class="ready-dot ${dotCls}"></span>
                <span class="text-white">${escHtml(p.name)}</span>
                ${badge}
            </div>`
        );
    });
    $('#lobby-msg').text(MSG.playersReady.replace(':ready', readyCount).replace(':total', players.length));
    const me      = players.find(p => p.id === myUserId);
    const isReady = me ? me.is_ready : false;
    const $btn    = $('#ready-btn');
    if (isReady) {
        $btn.removeClass('btn-success').addClass('btn-secondary')
            .html('<i class="fas fa-times mr-1"></i> ' + MSG.cancelReady);
    } else {
        $btn.removeClass('btn-secondary').addClass('btn-success')
            .html('<i class="fas fa-check mr-1"></i> ' + MSG.imReady);
    }
}

$('#ready-btn').on('click', function () {
    const $btn = $(this).prop('disabled', true);
    $.post(readyUrl, { _token: CSRF })
        .done(function (res) {
            if (res.game_started) {
                showGameSection();
                render(res.state);
            } else {
                if (res.error) notify('warning', res.error);
                if (res.players) updateLobby(res.players);
            }
        })
        .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || MSG.connectionError;
            notify('error', msg);
        })
        .always(function () { $btn.prop('disabled', false); });
});

// ── New Hand ──────────────────────────────────────────────────────────────
window.dealHand = function () {
    $('#new-hand-btn').prop('disabled', true);
    $.post(startUrl, { _token: CSRF })
        .done(function (res) { render(res.state); })
        .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || MSG.cantStartHand;
            notify('error', msg);
            $('#new-hand-btn').prop('disabled', false);
        });
};

// ── Actions ───────────────────────────────────────────────────────────────
window.sendAction = function (action, amount) {
    disableActions();
    $.post(actionUrl, { _token: CSRF, action, amount: amount || 0 })
        .done(function (res) { render(res.state); })
        .fail(function ()    { enableActions(); });
};

window.doRaise = function () {
    const s = gameState;
    if (!s) return;
    const myPlayer = s.players[s.my_index];
    if (!myPlayer) return;
    const slider = document.getElementById('raise-slider');
    const input  = document.getElementById('raise-input');
    const max    = myPlayer.chips + myPlayer.bet;
    slider.min   = s.min_raise;
    slider.max   = max;
    slider.step  = s.big_blind;
    slider.value = s.min_raise;
    input.min    = s.min_raise;
    input.max    = max;
    input.value  = s.min_raise;
    document.getElementById('raise-row').style.display = 'flex';
};

window.syncRaise = function (val) {
    document.getElementById('raise-slider').value = val;
    document.getElementById('raise-input').value  = val;
};

window.confirmRaise = function () {
    const val = parseInt(document.getElementById('raise-input').value, 10);
    hideRaiseRow();
    sendAction('raise', val);
};

window.hideRaiseRow = function () {
    document.getElementById('raise-row').style.display = 'none';
};

// ── Render ────────────────────────────────────────────────────────────────
function render(s) {
    gameState = s;
    renderPhase(s);
    renderCommunity(s);
    renderPot(s);
    renderPlayers(s);
    renderActions(s);
    renderLog(s);
    if (s.winner_info) showWinner(s.winner_info);
    else hideWinner();
    if (s.z_coins !== undefined) {
        $('#zcoin-balance').text(fmtChips(s.z_coins) + ' Z');
    }
    renderTurnTimer(s);

    // Polling fallback when no WebSocket and waiting for opponents
    if (!window.Echo && s.phase !== 'showdown' && !s.is_my_turn) {
        startPoll();
    } else {
        stopPoll();
    }
}

function renderPhase(s) {
    const labels = {
        preflop: 'PRE-FLOP', flop: 'FLOP',
        turn: 'TURN', river: 'RIVER', showdown: 'SHOWDOWN',
    };
    $('#phase-label').text(labels[s.phase] || s.phase.toUpperCase());
}

function renderCommunity(s) {
    const cc = s.community_cards;
    for (let i = 0; i < 5; i++) {
        const slot = $(`#cc-slot-${i}`);
        slot.empty().removeClass('card-placeholder');
        if (cc[i]) {
            slot.html(cardEl(cc[i]));
        } else {
            slot.addClass('card-placeholder');
        }
    }
}

function renderPot(s) {
    $('#pot-display').text(s.pot > 0 ? `POT: ${fmtChips(s.pot)}` : '');
}

function renderPlayers(s) {
    s.players.forEach(function (p) {
        const idx   = p.index;
        const $seat = $(`#seat-${idx}`);
        $seat.show();

        $seat.removeClass('folded active-turn');
        if (p.status === 'folded') $seat.addClass('folded');
        if (p.is_current && s.phase !== 'showdown') $seat.addClass('active-turn');

        let badges = '';
        if (p.is_dealer) badges += '<span class="badge-pos badge-d">D</span>';
        if (p.is_sb)     badges += '<span class="badge-pos badge-sb">SB</span>';
        if (p.is_bb)     badges += '<span class="badge-pos badge-bb">BB</span>';

        if (idx === 0) {
            $(`#seat-name-0`).text(p.name);
            $(`#seat-pos-0`).html(badges);
        } else {
            $(`#seat-name-${idx}`).html(badges + escHtml(p.name));
        }

        $(`#seat-chips-${idx}`).text(`${fmtChips(p.chips)}`);
        $(`#seat-bet-${idx}`).text(p.bet > 0 ? `Bet: ${fmtChips(p.bet)}` : '');

        // Cards
        const $cards = $(`#seat-cards-${idx}`);
        $cards.empty();
        p.hole_cards.forEach(function (c) {
            if (c.suit !== 'back') {
                const winnerCls = (s.phase === 'showdown' && p.is_winner) ? 'winner-glow' : '';
                $cards.append(cardEl(c, winnerCls));
            } else {
                $cards.append('<div class="card-back"></div>');
            }
        });

        // Hand name at showdown (for others)
        if (s.phase === 'showdown' && p.hand_name && idx !== s.my_index) {
            $cards.append(`<div class="mt-1" style="font-size:10px;color:#ffd700">${escHtml(p.hand_name)}</div>`);
        }

        // Thinking indicator
        const $box = $(`#seat-box-${idx}`);
        $box.find('.thinking').remove();
        if (p.is_current && p.is_ai && s.phase !== 'showdown') {
            $box.append('<div class="thinking mt-1">thinking...</div>');
        }

        // Status badges
        $box.find('.status-badge').remove();
        if (p.status === 'all-in') {
            $box.append('<div class="status-badge" style="font-size:10px;color:#e74c3c;font-weight:700">ALL-IN</div>');
        }
        if (p.status === 'folded') {
            $box.append('<div class="status-badge" style="font-size:10px;color:#888">FOLDED</div>');
        }
        if (s.phase === 'showdown' && p.is_winner) {
            $box.append('<div class="status-badge" style="font-size:11px;color:#ffe34d;font-weight:800">WINNER</div>');
        }
    });
}

function renderActions(s) {
    $('#action-btns').hide();
    $('#opponent-turn').hide();
    $('#showdown-msg').hide();
    hideRaiseRow();

    if (s.phase === 'showdown') {
        $('#showdown-msg').show();
        return;
    }
    if (!s.is_my_turn) {
        $('#opponent-turn').show();
        return;
    }

    enableActions();
    $('#action-btns').show();
    $('#btn-check').toggle(s.can_check);
    $('#btn-call').toggle(s.can_call);
    if (s.can_call) $('#call-amount').text(fmtChips(s.to_call));

    const myPlayer = s.players[s.my_index];
    const canRaise = myPlayer && myPlayer.chips > 0 && (myPlayer.chips + myPlayer.bet) > s.min_raise;
    $('#btn-raise').toggle(canRaise);
    $('#btn-allin').toggle(myPlayer && myPlayer.chips > 0);
}

function renderLog(s) {
    const $log = $('#action-log').empty();
    (s.log || []).slice().reverse().forEach(function (entry) {
        $log.append(`<div class="log-entry">${escHtml(entry)}</div>`);
    });
}

function showWinner(info) {
    const names = info.names.join(' & ');
    $('#winner-title').html(escHtml(names));
    $('#winner-hand').html(
        (info.folded_win ? 'Opponents folded' : `Hand: <strong>${info.hand_name}</strong>`)
        + ` &mdash; Pot: <strong>${fmtChips(info.pot)}</strong>`
    );
    $('#winner-banner').css('display', 'flex');
}

function hideWinner() {
    $('#winner-banner').hide();
}

// ── Turn timer ────────────────────────────────────────────────────────────
function renderTurnTimer(s) {
    if (turnInterval) { clearInterval(turnInterval); turnInterval = null; }

    if (!s.is_my_turn || !s.turn_started_at || s.phase === 'showdown') {
        $('#turn-timer').hide();
        return;
    }

    const LIMIT = 60;
    $('#turn-timer').show();

    function tick() {
        const elapsed   = Math.floor(Date.now() / 1000) - s.turn_started_at;
        const remaining = Math.max(0, LIMIT - elapsed);
        const pct       = (remaining / LIMIT) * 100;

        $('#timer-bar')
            .css('width', pct + '%')
            .toggleClass('bg-warning', remaining > 15)
            .toggleClass('bg-danger',  remaining <= 15);
        $('#timer-text').text(remaining + 's');
    }

    tick();
    turnInterval = setInterval(tick, 500);
}

// ── Polling fallback ──────────────────────────────────────────────────────
function startPoll() {
    if (pollTimer) return;
    pollTimer = setInterval(function () {
        if (pollXhr) return;   // skip if previous request still in flight
        pollXhr = $.get(stateUrl).done(function (res) {
            if (res.state) {
                render(res.state);
                if (res.state.is_my_turn || res.state.phase === 'showdown') stopPoll();
            }
        }).always(function () { pollXhr = null; });
    }, 3000);
}

function stopPoll() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    if (pollXhr)   { pollXhr.abort(); pollXhr = null; }
}

// ── Helpers ───────────────────────────────────────────────────────────────
const SUIT_SYM   = { spades: '♠', hearts: '♥', diamonds: '♦', clubs: '♣' };
const SUIT_COLOR = { spades: 'black', hearts: 'red', diamonds: 'red', clubs: 'black' };

function cardEl(c, extraCls) {
    const sym   = SUIT_SYM[c.suit]   || '?';
    const color = SUIT_COLOR[c.suit] || 'black';
    const cls   = 'play-card ' + color + (extraCls ? ' ' + extraCls : '');
    return `<div class="${cls}" title="${escHtml(c.rank)} of ${c.suit}">
        <div class="cc-top">${escHtml(c.rank)}<br>${sym}</div>
        <div class="cc-mid">${sym}</div>
        <div class="cc-bot">${escHtml(c.rank)}<br>${sym}</div>
    </div>`;
}

function fmtChips(n) {
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000)    return (n / 1000).toFixed(1) + 'k';
    return n.toLocaleString();
}

function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function disableActions() {
    $('#action-btns button').prop('disabled', true);
}

function enableActions() {
    $('#action-btns button').prop('disabled', false);
}
</script>
@endpush
