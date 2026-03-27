@extends('layouts.admin')

@section('title', $table->name . ' – Blackjack')

@push('styles')
<style>
/* ── Layout ────────────────────────────────────────── */
.bj-room {
    display: flex;
    flex-direction: column;
    gap: 0;
    min-height: calc(100vh - 130px);
}
.bj-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 0 12px;
    border-bottom: 1px solid #2d3f55;
    margin-bottom: 16px;
}
.bj-header-title {
    font-size: 15px;
    font-weight: 600;
    color: #e0e0e0;
    letter-spacing: 1px;
}

/* ── Felt table ────────────────────────────────────── */
.bj-felt-wrap {
    position: relative;
    display: flex;
    justify-content: center;
    padding: 8px 0;
}
.bj-felt {
    position: relative;
    width: 100%;
    max-width: 740px;
    background: radial-gradient(ellipse at center, #217a40 0%, #165c2e 60%, #0f3d1f 100%);
    border-radius: 180px;
    border: 10px solid #6b3a1f;
    box-shadow: 0 0 30px rgba(0,0,0,.7), inset 0 2px 8px rgba(255,255,255,.06);
    padding: 32px 60px;
    min-height: 340px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}
/* center stripe */
.bj-felt::before {
    content: '';
    position: absolute;
    left: 40px; right: 40px;
    top: 50%; transform: translateY(-50%);
    height: 1px;
    background: rgba(255,255,255,.08);
}

/* ── Dealer / Player areas ─────────────────────────── */
.bj-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    width: 100%;
}
.bj-area-label {
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(255,255,255,.5);
}
.bj-score-badge {
    display: inline-block;
    background: rgba(0,0,0,.45);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 20px;
    padding: 1px 10px;
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    min-width: 32px;
    text-align: center;
}
.bj-score-badge.bust  { background: rgba(192,57,43,.6); border-color: #e74c3c; }
.bj-score-badge.bj    { background: rgba(212,172,13,.6); border-color: #f6c23e; }
.bj-cards-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 6px;
}

/* ── Center info ───────────────────────────────────── */
.bj-center-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    min-height: 36px;
}
.bj-bet-label {
    font-size: 12px;
    color: rgba(255,255,255,.55);
    letter-spacing: 1px;
}
.bj-bet-amount {
    font-size: 18px;
    font-weight: 700;
    color: #f6c23e;
}
.bj-result-text {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 2px;
    text-shadow: 0 2px 8px rgba(0,0,0,.6);
    animation: bjPop .3s ease;
}
.bj-result-text.win      { color: #2ecc71; }
.bj-result-text.blackjack{ color: #f6c23e; }
.bj-result-text.dealer_bust{ color: #2ecc71; }
.bj-result-text.push     { color: #bdc3c7; }
.bj-result-text.bust     { color: #e74c3c; }
.bj-result-text.lose     { color: #e74c3c; }
@keyframes bjPop {
    from { transform: scale(.7); opacity: 0; }
    to   { transform: scale(1);  opacity: 1; }
}

/* ── CSS Play Cards (reuse poker style) ───────────── */
.play-card {
    display: inline-flex;
    flex-direction: column;
    justify-content: space-between;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 6px;
    width: 52px;
    height: 74px;
    padding: 3px 4px;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 1px 2px 5px rgba(0,0,0,.35);
    position: relative;
    user-select: none;
    flex-shrink: 0;
}
.play-card.red  { color: #c0392b; }
.play-card.black{ color: #1a1a2e; }
.play-card.lg {
    width: 68px;
    height: 96px;
    font-size: 13px;
    padding: 4px 5px;
}
.cc-top { line-height: 1.1; }
.cc-mid { text-align: center; font-size: 18px; line-height: 1; flex: 1; display: flex; align-items: center; justify-content: center; }
.cc-bot { line-height: 1.1; transform: rotate(180deg); align-self: flex-end; }
.play-card.lg .cc-mid { font-size: 24px; }
.card-back {
    background: repeating-linear-gradient(
        45deg, #1a3a6b, #1a3a6b 4px, #1e4a8a 4px, #1e4a8a 8px
    );
    color: transparent;
    border: 2px solid #fff;
}
.card-back .cc-top, .card-back .cc-mid, .card-back .cc-bot { visibility: hidden; }

/* ── Action panel ──────────────────────────────────── */
.bj-action-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 16px 0;
}
.bj-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
}
.bj-chip {
    min-width: 60px;
    padding: 6px 12px;
    border-radius: 30px;
    border: 2px solid #f6c23e;
    background: transparent;
    color: #f6c23e;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: background .15s, color .15s;
}
.bj-chip:hover, .bj-chip.active {
    background: #f6c23e;
    color: #1a1a2e;
}
.bj-bet-input-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}
#bj-bet-input {
    width: 120px;
    text-align: center;
    background: #1a2332;
    border: 1px solid #2d3f55;
    color: #f6c23e;
    border-radius: 6px;
    padding: 5px 10px;
    font-weight: 700;
    font-size: 15px;
}
.bj-action-btns {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
}
.bj-action-btns .btn { min-width: 100px; font-weight: 700; letter-spacing: 1px; }

/* ── Payout flash ──────────────────────────────────── */
.bj-payout-flash {
    font-size: 13px;
    font-weight: 600;
}
.bj-payout-flash.pos { color: #2ecc71; }
.bj-payout-flash.neg { color: #e74c3c; }
.bj-payout-flash.neu { color: #bdc3c7; }
</style>
@endpush

@section('content')
@include('partials.notify')
<script>
window.__bjTable  = {!! json_encode($table, JSON_HEX_TAG | JSON_HEX_APOS) !!};
window.__bjRoutes = {!! json_encode([
    'role'   => route('entertainment.blackjack.role',        $table),
    'ready'  => route('entertainment.blackjack.ready',       $table),
    'state'  => route('entertainment.blackjack.game.state',  $table),
    'start'  => route('entertainment.blackjack.game.start',  $table),
    'deal'   => route('entertainment.blackjack.game.deal',   $table),
    'action'       => route('entertainment.blackjack.game.action',        $table),
    'next'         => route('entertainment.blackjack.game.next',          $table),
    'dealerAction' => route('entertainment.blackjack.game.dealer-action', $table),
    'leave'  => route('entertainment.blackjack.leave',       $table),
], JSON_HEX_TAG | JSON_HEX_APOS) !!};
window.__bjMsg    = {!! json_encode([
    'confirmLeave' => __('messages.bj_confirm_leave'),
    'leaveTable'   => __('messages.bj_leave_table'),
    'dealer'       => __('messages.bj_dealer'),
    'yourBet'      => __('messages.bj_your_bet'),
    'you'          => __('messages.bj_you'),
    'deal'         => __('messages.bj_deal'),
    'newHand'      => __('messages.bj_new_hand'),
    'hit'          => __('messages.bj_hit'),
    'stand'        => __('messages.bj_stand'),
    'double'       => __('messages.bj_double'),
    'betRange'     => __('messages.bj_bet_range'),
    'players'      => __('messages.bj_players'),
    'invalidBet'    => __('messages.bj_invalid_bet', [':min' => '__MIN__', ':max' => '__MAX__']),
    'waitingRoom'   => __('messages.bj_waiting_room'),
    'imReady'       => __('messages.bj_im_ready'),
    'cancelReady'   => __('messages.bj_cancel_ready'),
    'ready'         => __('messages.bj_ready'),
    'waiting'       => __('messages.bj_waiting'),
    'playersReady'  => __('messages.bj_players_ready'),
    'startingIn'    => __('messages.bj_starting_in'),
    'loadingPlayers'  => __('messages.bj_loading_players'),
    'needMorePlayers' => __('messages.bj_need_more_players'),
    'results'       => [
        'blackjack'   => __('messages.bj_result_blackjack'),
        'win'         => __('messages.bj_result_win'),
        'dealer_bust' => __('messages.bj_result_dealer_bust'),
        'push'        => __('messages.bj_result_push'),
        'bust'        => __('messages.bj_result_bust'),
        'lose'        => __('messages.bj_result_lose'),
    ],
], JSON_HEX_TAG | JSON_HEX_APOS) !!};
</script>
<blackjack-room
    :table="window.__bjTable"
    :my-user-id="{{ Auth::id() }}"
    my-role="{{ $myRole }}"
    :my-seat="{{ $mySeat ?? 'null' }}"
    :routes="window.__bjRoutes"
    :msg="window.__bjMsg"
    csrf="{{ csrf_token() }}"
></blackjack-room>
@endsection
