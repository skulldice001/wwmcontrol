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
@include('partials.notify')
<script>
window.__pkTable  = {!! json_encode($table, JSON_HEX_TAG | JSON_HEX_APOS) !!};
window.__pkRoutes = {!! json_encode([
    'lobby'        => route('entertainment.poker'),
    'leave'        => route('entertainment.poker.leave',       $table),
    'ready'        => route('entertainment.poker.ready',       $table),
    'start'        => route('entertainment.poker.game.start',  $table),
    'state'        => route('entertainment.poker.game.state',  $table),
    'action'       => route('entertainment.poker.game.action', $table),
    'chatMessages' => route('entertainment.poker.chat.index',  $table),
    'chatSend'     => route('entertainment.poker.chat.send',   $table),
], JSON_HEX_TAG | JSON_HEX_APOS) !!};
window.__pkMsg    = {!! json_encode([
    'confirmLeave'        => __('messages.poker_confirm_leave'),
    'connectionError'     => __('messages.poker_connection_error'),
    'cantStartHand'       => __('messages.poker_cant_start_hand'),
    'imReady'             => __('messages.poker_im_ready'),
    'cancelReady'         => __('messages.poker_cancel_ready'),
    'playersReady'        => __('messages.poker_players_ready'),
    'backToLobby'         => __('messages.poker_lobby'),
    'leaveTable'          => __('messages.leave_table'),
    'blinds'              => __('messages.blinds'),
    'buyIn'               => __('messages.buy_in'),
    'loadingPlayers'      => __('messages.poker_loading_players'),
    'needMorePlayers'     => __('messages.poker_need_more_players'),
    'chatPanel'           => __('messages.poker_chat_panel'),
    'chatMe'              => __('messages.poker_chat_me'),
    'chatEmpty'           => __('messages.poker_chat_empty'),
    'chatPlaceholder'     => __('messages.poker_chat_placeholder'),
    'aiThinking'          => __('messages.poker_ai_thinking'),
    'opponentThinking'    => __('messages.poker_opponent_thinking'),
    'returningLobbyPre'   => __('messages.poker_returning_lobby_pre'),
    'returningLobbyPost'  => __('messages.poker_returning_lobby_post'),
    'statusAllin'         => __('messages.poker_status_allin'),
    'statusFolded'        => __('messages.poker_status_folded'),
    'statusWinner'        => __('messages.poker_status_winner'),
    'actionFold'          => __('messages.poker_action_fold'),
    'actionCheck'         => __('messages.poker_action_check'),
    'actionCall'          => __('messages.poker_action_call'),
    'actionRaise'         => __('messages.poker_action_raise'),
    'actionAllin'         => __('messages.poker_action_allin'),
    'raiseTo'             => __('messages.poker_raise_to'),
    'sideTable'           => __('messages.poker_side_table'),
    'sideLog'             => __('messages.poker_side_log'),
    'sideZoo'             => __('messages.poker_side_zoo'),
    'entryFeeLabel'       => __('messages.poker_entry_fee_label'),
    'perHand'             => __('messages.poker_per_hand'),
    'opponentsFolded'     => __('messages.poker_opponents_folded'),
    'handLabel'           => __('messages.poker_hand_label'),
    'potLabel'            => __('messages.poker_pot_label'),
    'betLabel'            => __('messages.poker_bet_label'),
    'potDisplay'          => __('messages.poker_pot_display'),
    'balanceLabel'        => __('messages.poker_balance_label'),
], JSON_HEX_TAG | JSON_HEX_APOS) !!};
</script>
<poker-room
    :table="window.__pkTable"
    :my-user-id="{{ Auth::id() }}"
    :initial-z-coins="{{ (int) Auth::user()->z_coins }}"
    :routes="window.__pkRoutes"
    :msg="window.__pkMsg"
    csrf="{{ csrf_token() }}"
></poker-room>
@endsection
