@extends('layouts.admin')

@section('title', $table->name . ' – Tài Xỉu')

@push('styles')
<style>
/* ── Tài Xỉu Room ──────────────────────────────────────── */
.tx-room { display: flex; gap: 14px; min-height: calc(100vh - 160px); }
.tx-main  { flex: 1; display: flex; flex-direction: column; gap: 12px; min-width: 0; }
.tx-side  { width: 230px; flex-shrink: 0; display: flex; flex-direction: column; gap: 10px; }

.tx-card {
    background: rgba(0,0,0,.55);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 12px;
    color: #ddd;
    overflow: hidden;
}
.tx-card-header {
    background: rgba(0,0,0,.35);
    border-bottom: 1px solid rgba(255,255,255,.08);
    padding: 10px 16px;
    font-weight: 700;
    font-size: 13px;
    color: #fff;
}

/* ── Dice area ────────────────────────────────────────── */
.tx-dice-area {
    display: flex; justify-content: center; align-items: center;
    gap: 36px; padding: 40px 0;
}

/* Scene = perspective container per die */
.tx-die-scene {
    width: 82px; height: 82px;
    perspective: 280px;
    perspective-origin: 50% 30%;
    flex-shrink: 0;
}

/* Drop-in when dice are revealed */
.tx-die-scene.dropping {
    animation: die-drop 0.5s ease-out both;
}
@keyframes die-drop {
    from { opacity: 0; transform: translateY(-28px) scale(0.8); }
    to   { opacity: 1; transform: translateY(0)     scale(1); }
}

/* Outcome glow (filter works on 3D elements, box-shadow doesn't) */
.tx-die-scene.glow-tai    { filter: drop-shadow(0 0 14px rgba(46,204,113,.85)); transition: filter .4s; }
.tx-die-scene.glow-xiu    { filter: drop-shadow(0 0 14px rgba(52,152,219,.85)); transition: filter .4s; }
.tx-die-scene.glow-triple { filter: drop-shadow(0 0 14px rgba(231,76,60,.9));   transition: filter .4s; }

/* ── 3D Cube ────────────────────────────────────────── */
.tx-die-cube {
    width: 82px; height: 82px;
    position: relative;
    transform-style: preserve-3d;
    transform-origin: 41px 41px 41px;
}

/* Six faces */
.tx-face {
    position: absolute; width: 82px; height: 82px;
    background: linear-gradient(145deg, #fdfdfd 0%, #e2e2e2 100%);
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,.10);
    display: grid; grid-template-columns: 1fr 1fr 1fr;
    grid-template-rows: 1fr 1fr 1fr;
    padding: 9px; gap: 5px;
    box-shadow:
        inset 0 2px 5px rgba(255,255,255,.95),
        inset 0 -2px 5px rgba(0,0,0,.12),
        0 0 0 1px rgba(0,0,0,.06);
    backface-visibility: visible;
}
/* Face positions – standard dice layout:
   1 opposite 6, 2 opposite 5, 3 opposite 4 */
.tx-face-1 { transform: rotateY(  0deg) translateZ(41px); }  /* front  */
.tx-face-6 { transform: rotateY(180deg) translateZ(41px); }  /* back   */
.tx-face-2 { transform: rotateY( 90deg) translateZ(41px); }  /* right  */
.tx-face-5 { transform: rotateY(-90deg) translateZ(41px); }  /* left   */
.tx-face-3 { transform: rotateX( 90deg) translateZ(41px); }  /* top    */
.tx-face-4 { transform: rotateX(-90deg) translateZ(41px); }  /* bottom */

/* Dots */
.tx-dot {
    width: 12px; height: 12px;
    background: radial-gradient(circle at 38% 32%, #444, #0d0d0d);
    border-radius: 50%; margin: auto;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.5), 0 1px 0 rgba(255,255,255,.15);
}
.tx-dot.hidden { visibility: hidden; }

/* ── Rolling: continuous diagonal spin ──────────────── */
.tx-die-cube.rolling {
    animation: cube-spin 0.75s linear infinite;
}
@keyframes cube-spin {
    0%   { transform: rotateX(  0deg) rotateY(  0deg); }
    100% { transform: rotateX(360deg) rotateY(360deg); }
}

/* ── Face target rotations (CSS custom props) ────────── */
.tx-die-cube[data-val="1"] { --rx:   0deg; --ry:   0deg; }
.tx-die-cube[data-val="2"] { --rx:   0deg; --ry: -90deg; }
.tx-die-cube[data-val="3"] { --rx: -90deg; --ry:   0deg; }
.tx-die-cube[data-val="4"] { --rx:  90deg; --ry:   0deg; }
.tx-die-cube[data-val="5"] { --rx:   0deg; --ry:  90deg; }
.tx-die-cube[data-val="6"] { --rx:   0deg; --ry: 180deg; }

/* ── Landing: spin 3× then settle on correct face ────── */
.tx-die-cube.landing {
    animation: cube-land 0.85s cubic-bezier(0.22, 0.85, 0.36, 1) both;
}
@keyframes cube-land {
    from { transform: rotateX(calc(var(--rx) + 1080deg)) rotateY(calc(var(--ry) + 1080deg)); }
    to   { transform: rotateX(var(--rx)) rotateY(var(--ry)); }
}

/* ── Revealed: hold at correct face ─────────────────── */
.tx-die-cube.revealed {
    transform: rotateX(var(--rx)) rotateY(var(--ry));
}

/* ── Outcome banner ───────────────────────────────────── */
.tx-outcome {
    text-align: center; padding: 10px;
    font-size: 22px; font-weight: 900; letter-spacing: 1px;
    border-radius: 8px; margin: 0 20px;
}
.tx-outcome.tai    { background: rgba(46,204,113,.15); color: #2ecc71; border: 1px solid #2ecc7155; }
.tx-outcome.xiu    { background: rgba(52,152,219,.15); color: #3498db; border: 1px solid #3498db55; }
.tx-outcome.triple { background: rgba(231, 76, 60,.15); color: #e74c3c; border: 1px solid #e74c3c55; }

/* ── Countdown bar ────────────────────────────────────── */
.tx-countdown { padding: 10px 16px; }
.tx-countdown-bar { height: 8px; background: rgba(255,255,255,.1); border-radius: 4px; overflow: hidden; }
.tx-countdown-fill { height: 100%; border-radius: 4px; transition: width 1s linear; }
.tx-countdown-text { font-size: 12px; color: #aaa; margin-top: 4px; text-align: right; }

/* ── Bet panel ────────────────────────────────────────── */
.tx-bet-panel { padding: 14px 16px; }
.tx-choice-btns { display: flex; gap: 8px; margin-bottom: 10px; }
.tx-choice-btn {
    flex: 1; padding: 12px 0; border: 2px solid transparent;
    border-radius: 8px; font-weight: 800; font-size: 15px; cursor: pointer;
    transition: all .2s;
}
.tx-choice-btn.tai { background: rgba(46,204,113,.12); border-color: #2ecc7155; color: #2ecc71; }
.tx-choice-btn.tai.active { background: #2ecc71; color: #000; border-color: #2ecc71; }
.tx-choice-btn.xiu { background: rgba(52,152,219,.12); border-color: #3498db55; color: #3498db; }
.tx-choice-btn.xiu.active { background: #3498db; color: #fff; border-color: #3498db; }
.tx-amount-row { display: flex; gap: 8px; align-items: center; }
.tx-amount-row input { flex: 1; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15); color: #fff; border-radius: 6px; padding: 6px 10px; }
.tx-shortcuts { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 8px; }
.tx-shortcut { background: rgba(255,255,255,.1); border: none; color: #fff; border-radius: 4px; padding: 3px 8px; font-size: 11px; cursor: pointer; }
.tx-shortcut:hover { background: rgba(255,255,255,.2); }

/* ── Bets table ───────────────────────────────────────── */
.tx-bets { width: 100%; font-size: 12px; border-collapse: collapse; }
.tx-bets th { color: #aaa; font-weight: 600; padding: 4px 8px; border-bottom: 1px solid rgba(255,255,255,.07); }
.tx-bets td { padding: 5px 8px; border-bottom: 1px solid rgba(255,255,255,.04); }
.tx-bets .tx-tai { color: #2ecc71; font-weight: 700; }
.tx-bets .tx-xiu { color: #3498db; font-weight: 700; }
.tx-bets .result-win    { color: #2ecc71; }
.tx-bets .result-lose   { color: #e74c3c; }
.tx-bets .result-triple { color: #e74c3c; }

/* ── Chat ─────────────────────────────────────────────── */
.tx-chat-panel { flex: 1; display: flex; flex-direction: column; }
.tx-chat-messages { flex: 1; overflow-y: auto; padding: 8px 12px; max-height: 220px; }
.tx-chat-msg { margin-bottom: 6px; }
.tx-chat-meta { display: flex; justify-content: space-between; margin-bottom: 1px; }
.tx-chat-name { font-size: 11px; font-weight: 700; color: #aaa; }
.tx-chat-time { font-size: 10px; color: #666; }
.tx-chat-bubble { font-size: 12px; color: #ddd; background: rgba(255,255,255,.05); border-radius: 6px; padding: 4px 8px; display: inline-block; }
.tx-chat-mine .tx-chat-bubble { background: rgba(52,152,219,.15); color: #7ecbf5; }
.tx-chat-empty { color: #555; font-size: 12px; text-align: center; padding: 10px 0; }
.tx-chat-input-row { display: flex; padding: 8px; gap: 6px; border-top: 1px solid rgba(255,255,255,.08); }
.tx-chat-input { flex: 1; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); color: #fff; border-radius: 6px; padding: 5px 10px; font-size: 12px; }
.tx-chat-send { background: #3498db; border: none; color: #fff; border-radius: 6px; padding: 5px 10px; cursor: pointer; }

/* ── My result banner ─────────────────────────────────── */
.tx-my-result { text-align: center; padding: 14px; margin: 0 0 8px; border-radius: 8px; font-weight: 800; font-size: 18px; }
.tx-my-result.win    { background: rgba(46,204,113,.15); color: #2ecc71; }
.tx-my-result.lose   { background: rgba(231,76,60,.12);  color: #e74c3c; }
.tx-my-result.triple { background: rgba(231,76,60,.12);  color: #e74c3c; }

/* ── Log ──────────────────────────────────────────────── */
.tx-log-entry { font-size: 11px; color: #aaa; border-bottom: 1px solid rgba(255,255,255,.04); padding: 2px 0; }
.tx-log-entry:last-child { border-bottom: none; }

/* Loading */
.tx-loading-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,.6);
    display: flex; align-items: center; justify-content: center;
}
</style>
@endpush

@section('content')
@include('partials.notify')
<script>
window.__txTable  = {!! json_encode($table, JSON_HEX_TAG | JSON_HEX_APOS) !!};
window.__txRoutes = {!! json_encode([
    'lobby'        => route('entertainment.taixiu'),
    'leave'        => route('entertainment.taixiu.leave',       $table),
    'state'        => route('entertainment.taixiu.game.state',  $table),
    'bet'          => route('entertainment.taixiu.game.bet',    $table),
    'chatMessages' => route('entertainment.taixiu.chat.index',  $table),
    'chatSend'     => route('entertainment.taixiu.chat.send',   $table),
], JSON_HEX_TAG | JSON_HEX_APOS) !!};
window.__txMsg = {!! json_encode([
    'leaveTable'      => __('messages.tx_leave_table'),
    'confirmLeave'    => __('messages.tx_confirm_leave'),
    'backToLobby'     => __('messages.taixiu_lobby'),
    'tai'             => __('messages.tx_tai'),
    'xiu'             => __('messages.tx_xiu'),
    'triple'          => __('messages.tx_triple'),
    'betting'         => __('messages.tx_betting'),
    'waiting'         => __('messages.tx_waiting_round'),
    'resultWin'       => __('messages.tx_result_win'),
    'resultLose'      => __('messages.tx_result_lose'),
    'resultTriple'    => __('messages.tx_result_triple'),
    'confirmBet'      => __('messages.tx_confirm_bet'),
    'total'           => __('messages.tx_total'),
    'secsLeft'        => __('messages.tx_secs_left'),
    'betTotal'        => __('messages.tx_bet_total'),
    'player'          => __('messages.tx_player'),
    'choice'          => __('messages.tx_choice'),
    'amount'          => __('messages.tx_amount'),
    'result'          => __('messages.tx_result'),
    'chatPanel'       => __('messages.poker_chat_panel'),
    'chatMe'          => __('messages.poker_chat_me'),
    'chatEmpty'       => __('messages.poker_chat_empty'),
    'chatPlaceholder' => __('messages.poker_chat_placeholder'),
    'log'             => __('messages.poker_side_log'),
    'balance'         => __('messages.poker_balance_label'),
], JSON_HEX_TAG | JSON_HEX_APOS) !!};
</script>
<taixiu-room
    :table="window.__txTable"
    :my-user-id="{{ Auth::id() }}"
    :routes="window.__txRoutes"
    :msg="window.__txMsg"
    csrf="{{ csrf_token() }}"
    :initial-z-coins="{{ (int) (Auth::user()->z_coins - Auth::user()->z_coins_frozen) }}"
></taixiu-room>
@endsection
