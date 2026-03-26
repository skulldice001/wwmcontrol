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
<div class="bj-room">

    {{-- Header --}}
    <div class="bj-header">
        <a href="{{ route('entertainment.blackjack') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>{{ __('messages.bj_back_to_lobby') }}
        </a>
        <span class="bj-header-title">{{ strtoupper($table->name) }}</span>
        <span>
            <i class="fas fa-coins mr-1" style="color:#f6c23e;"></i>
            <span id="zcoin-balance" class="font-weight-bold" style="color:#f6c23e;">—</span>
            <small class="text-muted ml-1">Z</small>
        </span>
    </div>

    {{-- Felt Table --}}
    <div class="bj-felt-wrap">
        <div class="bj-felt">

            {{-- Dealer --}}
            <div class="bj-area">
                <div class="d-flex align-items-center gap-2" style="gap:8px;">
                    <span class="bj-area-label">{{ __('messages.bj_dealer') }}</span>
                    <span id="dealer-score" class="bj-score-badge">—</span>
                </div>
                <div id="dealer-cards" class="bj-cards-row"></div>
            </div>

            {{-- Center --}}
            <div class="bj-center-info" id="bj-center">
                <span class="bj-bet-label" id="bj-bet-label" style="display:none;">{{ __('messages.bj_your_bet') }}</span>
                <span class="bj-bet-amount" id="bj-bet-amount"></span>
                <span class="bj-result-text" id="bj-result-text" style="display:none;"></span>
                <span class="bj-payout-flash" id="bj-payout-flash"></span>
            </div>

            {{-- Player --}}
            <div class="bj-area">
                <div id="player-cards" class="bj-cards-row"></div>
                <div class="d-flex align-items-center" style="gap:8px;">
                    <span class="bj-area-label">{{ __('messages.bj_you') }}</span>
                    <span id="player-score" class="bj-score-badge">—</span>
                </div>
            </div>

        </div>
    </div>

    {{-- Action Panel --}}
    <div class="bj-action-panel">

        {{-- Bet controls (betting / finished) --}}
        <div id="bet-controls">
            <div class="bj-chip-row mb-2" id="chip-row"></div>
            <div class="bj-bet-input-wrap">
                <input type="number" id="bj-bet-input"
                       min="{{ $table->min_bet }}" max="{{ $table->max_bet }}"
                       value="{{ $table->min_bet }}" step="100">
                <button id="deal-btn" class="btn btn-success font-weight-bold px-4">
                    <i class="fas fa-play mr-1"></i><span id="deal-btn-text">{{ __('messages.bj_deal') }}</span>
                </button>
            </div>
            <div class="text-muted text-center mt-1" style="font-size:11px;">
                {{ __('messages.bj_bet_range') }}:
                {{ number_format($table->min_bet) }} – {{ number_format($table->max_bet) }} Z
            </div>
        </div>

        {{-- Game action buttons (playing) --}}
        <div id="game-controls" class="bj-action-btns" style="display:none;">
            <button class="btn btn-warning" onclick="sendAction('hit')">
                <i class="fas fa-plus mr-1"></i>{{ __('messages.bj_hit') }}
            </button>
            <button class="btn btn-secondary" onclick="sendAction('stand')">
                <i class="fas fa-hand-paper mr-1"></i>{{ __('messages.bj_stand') }}
            </button>
            <button class="btn btn-danger" id="btn-double" onclick="sendAction('double')">
                <i class="fas fa-times-circle mr-1"></i>{{ __('messages.bj_double') }}
            </button>
        </div>

    </div>
</div>
@endsection

@include('partials.notify')

@push('scripts')
<script>
const ROUTES = {
    state:  '{{ route('entertainment.blackjack.game.state',  $table) }}',
    deal:   '{{ route('entertainment.blackjack.game.deal',   $table) }}',
    action: '{{ route('entertainment.blackjack.game.action', $table) }}',
};
const TABLE = {
    minBet: {{ $table->min_bet }},
    maxBet: {{ $table->max_bet }},
};
const MSG = {
    blackjack:   '{{ __('messages.bj_result_blackjack') }}',
    win:         '{{ __('messages.bj_result_win') }}',
    dealer_bust: '{{ __('messages.bj_result_dealer_bust') }}',
    push:        '{{ __('messages.bj_result_push') }}',
    bust:        '{{ __('messages.bj_result_bust') }}',
    lose:        '{{ __('messages.bj_result_lose') }}',
    deal:        '{{ __('messages.bj_deal') }}',
    newHand:     '{{ __('messages.bj_new_hand') }}',
};
const SUIT_SYM   = { spades:'♠', hearts:'♥', diamonds:'♦', clubs:'♣' };
const SUIT_COLOR = { spades:'black', hearts:'red', diamonds:'red', clubs:'black' };
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ── Card rendering ────────────────────────────────────────────────────
function cardEl(c, lg) {
    if (!c || c.suit === 'back') {
        return `<div class="play-card card-back${lg?' lg':''}">
            <div class="cc-top"></div><div class="cc-mid"></div><div class="cc-bot"></div>
        </div>`;
    }
    const sym   = SUIT_SYM[c.suit]   || '?';
    const color = SUIT_COLOR[c.suit] || 'black';
    const rank  = escHtml(c.rank);
    return `<div class="play-card ${color}${lg?' lg':''}" title="${rank} of ${c.suit}">
        <div class="cc-top">${rank}<br>${sym}</div>
        <div class="cc-mid">${sym}</div>
        <div class="cc-bot">${rank}<br>${sym}</div>
    </div>`;
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Build preset chip buttons ─────────────────────────────────────────
(function buildChips() {
    const PRESETS = [100, 500, 1000, 5000, 10000, 50000, 100000];
    const row = document.getElementById('chip-row');
    const input = document.getElementById('bj-bet-input');
    PRESETS.filter(v => v >= TABLE.minBet && v <= TABLE.maxBet).forEach(v => {
        const btn = document.createElement('button');
        btn.className = 'bj-chip';
        btn.textContent = v >= 1000 ? (v/1000)+'K' : v;
        btn.addEventListener('click', () => {
            input.value = v;
            row.querySelectorAll('.bj-chip').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
        row.appendChild(btn);
    });
    // Also sync chip highlight when user types
    input.addEventListener('input', () => {
        row.querySelectorAll('.bj-chip').forEach(b => b.classList.remove('active'));
    });
})();

// ── Render state ──────────────────────────────────────────────────────
let _currentPhase = null;

function render(s) {
    _currentPhase = s.phase;

    // Z-coin balance
    if (s.z_coins !== undefined) {
        document.getElementById('zcoin-balance').textContent = Number(s.z_coins).toLocaleString();
    }

    if (s.phase === 'betting') {
        showBetControls(false);
        clearTable();
        return;
    }

    // Dealer cards
    const dealerEl = document.getElementById('dealer-cards');
    dealerEl.innerHTML = (s.dealer_cards || []).map(c => cardEl(c, false)).join('');

    // Player cards (slightly larger)
    const playerEl = document.getElementById('player-cards');
    playerEl.innerHTML = (s.player_cards || []).map(c => cardEl(c, true)).join('');

    // Scores
    const dScore = document.getElementById('dealer-score');
    const pScore = document.getElementById('player-score');

    dScore.textContent  = s.dealer_score ?? '—';
    pScore.textContent  = s.player_score ?? '—';
    dScore.className    = 'bj-score-badge';
    pScore.className    = 'bj-score-badge';

    if (s.phase === 'finished') {
        if (s.player_score > 21) pScore.classList.add('bust');
        if (s.is_blackjack)      pScore.classList.add('bj');
        if (s.dealer_bj)         dScore.classList.add('bj');
        if (s.dealer_score > 21) dScore.classList.add('bust');
    }

    // Bet display
    document.getElementById('bj-bet-label').style.display  = 'inline';
    document.getElementById('bj-bet-amount').textContent   = s.bet ? Number(s.bet).toLocaleString() + ' Z' : '';

    if (s.phase === 'playing') {
        document.getElementById('bj-result-text').style.display = 'none';
        document.getElementById('bj-payout-flash').textContent  = '';
        showGameControls(s.can_double);
    }

    if (s.phase === 'finished') {
        showResult(s);
        showBetControls(true); // show bet controls for next hand
    }
}

function clearTable() {
    document.getElementById('dealer-cards').innerHTML  = '';
    document.getElementById('player-cards').innerHTML  = '';
    document.getElementById('dealer-score').textContent = '—';
    document.getElementById('player-score').textContent = '—';
    document.getElementById('bj-bet-label').style.display  = 'none';
    document.getElementById('bj-bet-amount').textContent   = '';
    document.getElementById('bj-result-text').style.display = 'none';
    document.getElementById('bj-payout-flash').textContent  = '';
    document.getElementById('dealer-score').className = 'bj-score-badge';
    document.getElementById('player-score').className = 'bj-score-badge';
}

function showResult(s) {
    const el = document.getElementById('bj-result-text');
    el.textContent  = MSG[s.result] || s.result;
    el.className    = 'bj-result-text ' + (s.result || '');
    el.style.display = 'inline';

    // Payout flash
    const flash = document.getElementById('bj-payout-flash');
    if (s.result === 'push') {
        flash.textContent = '± 0 Z';
        flash.className   = 'bj-payout-flash neu';
    } else if (['win','blackjack','dealer_bust'].includes(s.result)) {
        const profit = s.result === 'blackjack'
            ? '+' + Math.round(s.bet * 1.5).toLocaleString()
            : '+' + Number(s.bet).toLocaleString();
        flash.textContent = profit + ' Z';
        flash.className   = 'bj-payout-flash pos';
    } else {
        flash.textContent = '–' + Number(s.bet).toLocaleString() + ' Z';
        flash.className   = 'bj-payout-flash neg';
    }
}

function showBetControls(isNewHand) {
    document.getElementById('bet-controls').style.display   = '';
    document.getElementById('game-controls').style.display  = 'none';
    document.getElementById('deal-btn-text').textContent = isNewHand ? MSG.newHand : MSG.deal;
}
function showGameControls(canDouble) {
    document.getElementById('bet-controls').style.display  = 'none';
    document.getElementById('game-controls').style.display = '';
    document.getElementById('btn-double').disabled = !canDouble;
}

// ── API calls ─────────────────────────────────────────────────────────
function loadState() {
    fetch(ROUTES.state)
        .then(r => r.json())
        .then(data => {
            if (data.state) {
                render(data.state);
                // sync z_coins if present at top level
                if (data.z_coins !== undefined) {
                    document.getElementById('zcoin-balance').textContent =
                        Number(data.z_coins).toLocaleString();
                }
            } else {
                // No active game
                render({ phase: 'betting', z_coins: data.z_coins });
            }
        })
        .catch(() => notify('error', 'Connection error.'));
}

document.getElementById('deal-btn').addEventListener('click', function () {
    const bet = parseInt(document.getElementById('bj-bet-input').value, 10);
    if (!bet || bet < TABLE.minBet || bet > TABLE.maxBet) {
        const msg = '{{ __('messages.bj_invalid_bet', [':min' => '__MIN__', ':max' => '__MAX__']) }}'
            .replace('__MIN__', Number(TABLE.minBet).toLocaleString())
            .replace('__MAX__', Number(TABLE.maxBet).toLocaleString());
        notify('warning', msg);
        return;
    }
    this.disabled = true;
    fetch(ROUTES.deal, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ bet }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { notify('error', data.error); return; }
        render(data.state);
    })
    .catch(() => notify('error', 'Connection error.'))
    .finally(() => { this.disabled = false; });
});

function sendAction(action) {
    document.getElementById('game-controls').querySelectorAll('button')
        .forEach(b => b.disabled = true);

    fetch(ROUTES.action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ action }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.error) { notify('error', data.error); return; }
        render(data.state);
    })
    .catch(() => notify('error', 'Connection error.'))
    .finally(() => {
        document.getElementById('game-controls').querySelectorAll('button')
            .forEach(b => b.disabled = false);
    });
}

// ── Init ──────────────────────────────────────────────────────────────
loadState();
</script>
@endpush
