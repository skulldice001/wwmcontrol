<template>
  <!-- Header (always visible) -->
  <div class="row mb-2">
    <div class="col-12 d-flex align-items-center justify-content-between">
      <a :href="routes.lobby" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left"></i> {{ msg.backToLobby }}
      </a>
      <span class="text-muted small">
        {{ table.name }} &mdash; {{ msg.blinds }}: {{ fmt(table.small_blind) }}/{{ fmt(table.big_blind) }}
      </span>
      <form ref="leaveForm" :action="routes.leave" method="POST" class="d-inline">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="DELETE">
        <button type="button" class="btn btn-sm btn-danger" @click="confirmLeave">
          <i class="fas fa-sign-out-alt"></i> {{ msg.leaveTable }}
        </button>
      </form>
    </div>
  </div>

  <!-- ── Lobby section ───────────────────────────────── -->
  <div v-if="!inGame">
    <div class="row justify-content-center mt-3">
      <div class="col-md-5 col-lg-4 d-flex flex-column gap-3">
        <div class="lobby-card card">
          <div class="card-header">
            <h5 class="mb-0 text-white">
              <i class="fas fa-users mr-2"></i>{{ table.name }}
            </h5>
            <small class="text-muted">
              {{ msg.blinds }}: {{ fmt(table.small_blind) }}/{{ fmt(table.big_blind) }}
              &nbsp;&middot;&nbsp;
              {{ msg.buyIn }}: {{ fmt(table.max_buy_in) }}
            </small>
          </div>

          <!-- Player list -->
          <div class="card-body p-0">
            <div v-if="lobbyLoading" class="text-center py-4 text-muted">
              <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
              {{ msg.loadingPlayers }}
            </div>
            <div v-else>
              <div v-for="p in lobbyPlayers" :key="p.id" class="lobby-player-item">
                <span :class="['ready-dot', p.is_ready ? 'is-ready' : 'not-ready']"></span>
                <span class="text-white">{{ p.name }}</span>
                <span v-if="p.is_ready" class="badge badge-success ml-auto">Ready</span>
                <span v-else class="badge badge-secondary ml-auto">Waiting</span>
              </div>
            </div>
          </div>

          <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <small class="text-muted">
                <i class="fas fa-coins" style="color:#f6c23e;"></i>
                {{ msg.entryFeeLabel }}: <strong style="color:#f6c23e;">{{ fmt(table.big_blind) }} Zoo</strong> {{ msg.perHand }}
              </small>
              <small class="text-muted">
                {{ msg.balanceLabel }} <strong style="color:#f6c23e;">{{ fmtChips(zCoins) }} Zoo</strong>
              </small>
            </div>
            <div v-if="lobbyPlayers.length < 2" class="pk-need-players-notice mb-2">
              <i class="fas fa-users mr-2"></i>{{ msg.needMorePlayers }}
            </div>
            <div v-else class="text-muted small mb-2">{{ lobbyMsg }}</div>
            <button
              :class="['btn btn-lg px-5', myIsReady ? 'btn-secondary' : 'btn-success']"
              :disabled="readyLoading || lobbyPlayers.length < 2"
              @click="toggleReady"
            >
              <template v-if="myIsReady">
                <i class="fas fa-times mr-1"></i> {{ msg.cancelReady }}
              </template>
              <template v-else>
                <i class="fas fa-check mr-1"></i> {{ msg.imReady }}
              </template>
            </button>
          </div>
        </div>

        <!-- Chat panel (lobby view) -->
        <div class="pk-chat-panel lobby-card mt-3">
          <div class="card-header" style="padding:8px 14px;">
            <h6 class="mb-0" style="font-size:13px;"><i class="fas fa-comments mr-1"></i> {{ msg.chatPanel }}</h6>
          </div>
          <div class="pk-chat-messages" ref="chatBox" style="max-height:180px;">
            <div v-for="m in chatMessages" :key="m.id"
                 :class="['pk-chat-msg', m.user_id === myUserId ? 'pk-chat-mine' : '']">
              <div class="pk-chat-meta">
                <span class="pk-chat-name">{{ m.user_id === myUserId ? msg.chatMe : m.name }}</span>
                <span class="pk-chat-time">{{ m.time }}</span>
              </div>
              <div class="pk-chat-bubble">{{ m.message }}</div>
            </div>
            <div v-if="chatMessages.length === 0" class="pk-chat-empty">{{ msg.chatEmpty }}</div>
          </div>
          <div class="pk-chat-input-row" style="border-top:1px solid rgba(255,255,255,.1);">
            <input
              v-model="chatInput"
              class="pk-chat-input"
              :placeholder="msg.chatPlaceholder"
              maxlength="500"
              @keydown.enter.prevent="sendChatMessage"
              :disabled="chatSending"
            >
            <button class="pk-chat-send" @click="sendChatMessage" :disabled="chatSending || !chatInput.trim()">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Game section ────────────────────────────────── -->
  <div v-else class="poker-room">

    <!-- Main Table Column -->
    <div class="table-col">

      <!-- Felt table -->
      <div class="table-wrap">
        <div class="felt">

          <!-- Community cards + pot -->
          <div class="community-area">
            <div class="phase-badge">{{ phaseLabel }}</div>
            <div class="community-cards">
              <template v-for="(c, i) in communityCards" :key="i">
                <div v-if="c" :class="['play-card', suitColor(c.suit)]" :title="`${c.rank} of ${c.suit}`">
                  <div class="cc-top">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
                  <div class="cc-mid">{{ suitSym(c.suit) }}</div>
                  <div class="cc-bot">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
                </div>
                <div v-else class="card-placeholder"></div>
              </template>
            </div>
            <div class="pot-display">{{ potText }}</div>
          </div>

          <!-- Winner overlay -->
          <div id="winner-banner" :style="{ display: winnerInfo ? 'flex' : 'none' }">
            <h4>{{ winnerNames }}</h4>
            <p v-html="winnerDesc"></p>
          </div>

          <!-- AI Seats 1–5 (positioned absolutely around oval) -->
          <div
            v-for="seat in aiSeats" :key="seat.index"
            v-show="seat.visible"
            :class="['seat', `seat-${seat.index}`, { folded: seat.folded, 'active-turn': seat.activeTurn }]"
          >
            <div class="seat-box">
              <div class="seat-name" v-html="seat.nameHtml"></div>
              <div class="seat-chips">{{ seat.chips }}</div>
              <div class="seat-bet">{{ seat.bet }}</div>
              <div class="seat-cards">
                <template v-for="(c, ci) in seat.cards" :key="ci">
                  <div
                    v-if="c.suit !== 'back'"
                    :class="['play-card', suitColor(c.suit), seat.isWinner && phase === 'showdown' ? 'winner-glow' : '']"
                    :title="`${c.rank} of ${c.suit}`"
                  >
                    <div class="cc-top">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
                    <div class="cc-mid">{{ suitSym(c.suit) }}</div>
                    <div class="cc-bot">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
                  </div>
                  <div v-else class="card-back"></div>
                </template>
                <div
                  v-if="phase === 'showdown' && seat.handName && gameState && seat.index !== gameState.my_index"
                  class="mt-1" style="font-size:10px;color:#ffd700"
                >{{ seat.handName }}</div>
              </div>
              <div v-if="seat.isCurrent && seat.isAI && phase !== 'showdown'" class="thinking mt-1">{{ msg.aiThinking }}</div>
              <div v-if="seat.status === 'all-in'" class="status-badge" style="font-size:10px;color:#e74c3c;font-weight:700">{{ msg.statusAllin }}</div>
              <div v-if="seat.status === 'folded'" class="status-badge" style="font-size:10px;color:#888">{{ msg.statusFolded }}</div>
              <div v-if="phase === 'showdown' && seat.isWinner" class="status-badge" style="font-size:11px;color:#ffe34d;font-weight:800">{{ msg.statusWinner }}</div>
            </div>
          </div>

        </div><!-- /felt -->
      </div><!-- /table-wrap -->

      <!-- Human seat (seat-0, below table) -->
      <div
        :class="['seat seat-0', { folded: mySeat.folded, 'active-turn': mySeat.activeTurn }]"
        style="position:relative;transform:none;left:auto;bottom:auto;"
      >
        <div class="seat-box">
          <div class="d-flex justify-content-between align-items-center">
            <span class="seat-name">{{ mySeat.name }}</span>
            <span v-html="mySeat.posHtml"></span>
          </div>
          <div class="seat-chips">{{ mySeat.chips }}</div>
          <div class="seat-bet">{{ mySeat.bet }}</div>
          <div class="my-cards">
            <template v-for="(c, ci) in mySeat.cards" :key="ci">
              <div
                v-if="c.suit !== 'back'"
                :class="['play-card', suitColor(c.suit), mySeat.isWinner && phase === 'showdown' ? 'winner-glow' : '']"
                :title="`${c.rank} of ${c.suit}`"
              >
                <div class="cc-top">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
                <div class="cc-mid">{{ suitSym(c.suit) }}</div>
                <div class="cc-bot">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
              </div>
              <div v-else class="card-back"></div>
            </template>
          </div>
          <div v-if="mySeat.status === 'all-in'" class="status-badge" style="font-size:10px;color:#e74c3c;font-weight:700">{{ msg.statusAllin }}</div>
          <div v-if="mySeat.status === 'folded'" class="status-badge" style="font-size:10px;color:#888">{{ msg.statusFolded }}</div>
          <div v-if="phase === 'showdown' && mySeat.isWinner" class="status-badge" style="font-size:11px;color:#ffe34d;font-weight:800">{{ msg.statusWinner }}</div>
        </div>
      </div>

      <!-- Action panel -->
      <div class="action-panel">

        <!-- Turn countdown timer -->
        <div id="turn-timer" v-show="showTimer">
          <div class="progress">
            <div
              id="timer-bar"
              class="progress-bar"
              :class="timerDanger ? 'bg-danger' : 'bg-warning'"
              role="progressbar"
              :style="{ width: timerPct + '%', transition: 'none' }"
            ></div>
          </div>
          <div class="text-center" id="timer-text">{{ timerSec }}s</div>
        </div>

        <!-- My turn -->
        <div v-if="isMyTurn && phase !== 'showdown'">
          <div class="d-flex gap-2 flex-wrap justify-content-center">
            <button class="btn btn-danger" @click="sendAction('fold')">
              <i class="fas fa-times"></i> {{ msg.actionFold }}
            </button>
            <button v-show="canCheck" class="btn btn-secondary" @click="sendAction('check')">
              <i class="fas fa-hand-paper"></i> {{ msg.actionCheck }}
            </button>
            <button v-show="canCall" class="btn btn-warning" @click="sendAction('call')">
              <i class="fas fa-hand-holding-usd"></i> {{ msg.actionCall }} {{ callAmountText }}
            </button>
            <button v-show="canRaise" class="btn btn-primary" @click="doRaise">
              <i class="fas fa-chevron-up"></i> {{ msg.actionRaise }}
            </button>
            <button v-show="canAllin" class="btn btn-outline-warning" @click="sendAction('allin')">
              {{ msg.actionAllin }}
            </button>
          </div>
          <div class="raise-row" v-show="showRaiseRow">
            <span class="text-white small">{{ msg.raiseTo }}</span>
            <input type="range" v-model.number="raiseValue"
                   :min="raiseMin" :max="raiseMax" :step="raiseStep">
            <input type="number" v-model.number="raiseValue"
                   :min="raiseMin" :max="raiseMax" :step="raiseStep"
                   class="form-control form-control-sm" style="width:100px">
            <button class="btn btn-sm btn-primary" @click="confirmRaise">OK</button>
            <button class="btn btn-sm btn-secondary" @click="showRaiseRow = false">X</button>
          </div>
        </div>

        <!-- Opponent's turn -->
        <div v-else-if="phase !== 'showdown'" class="text-center text-muted small py-2">
          <span class="thinking">{{ msg.opponentThinking }}</span>
        </div>

        <!-- Showdown: auto-return to lobby countdown -->
        <div v-if="phase === 'showdown'" class="text-center py-2">
          <div class="text-muted small">
            <i class="fas fa-hourglass-half mr-1"></i>
            {{ msg.returningLobbyPre }} <strong class="text-warning">{{ lobbyCountdown }}</strong> {{ msg.returningLobbyPost }}
          </div>
        </div>

      </div><!-- /action-panel -->

    </div><!-- /table-col -->

    <!-- Side Panel -->
    <div class="side-col">
      <div class="side-card">
        <h6><i class="fas fa-info-circle mr-1"></i> {{ msg.sideTable }}</h6>
        <div>{{ table.name }}</div>
        <div class="text-muted">{{ msg.blinds }}: {{ fmt(table.small_blind) }}/{{ fmt(table.big_blind) }}</div>
        <div class="text-muted">{{ msg.entryFeeLabel }}: {{ fmt(table.big_blind) }} Zoo</div>
      </div>
      <div class="side-card" style="flex:1;overflow-y:auto;">
        <h6><i class="fas fa-scroll mr-1"></i> {{ msg.sideLog }}</h6>
        <div v-for="(entry, i) in reversedLog" :key="i" class="log-entry">{{ entry }}</div>
      </div>
      <div class="side-card">
        <h6><i class="fas fa-coins mr-1" style="color:#f6c23e;"></i> {{ msg.sideZoo }}</h6>
        <div style="font-size:18px;font-weight:800;color:#f6c23e;letter-spacing:.5px;">{{ fmtChips(zCoins) }}</div>
        <div class="text-muted" style="font-size:11px;">{{ msg.entryFeeLabel }}: {{ fmt(table.big_blind) }} Zoo {{ msg.perHand }}</div>
      </div>
      <!-- Chat panel (game view) -->
      <div class="side-card pk-chat-panel">
        <h6><i class="fas fa-comments mr-1"></i> {{ msg.chatPanel }}</h6>
        <div class="pk-chat-messages" ref="chatBox">
          <div v-for="m in chatMessages" :key="m.id"
               :class="['pk-chat-msg', m.user_id === myUserId ? 'pk-chat-mine' : '']">
            <div class="pk-chat-meta">
              <span class="pk-chat-name">{{ m.user_id === myUserId ? msg.chatMe : m.name }}</span>
              <span class="pk-chat-time">{{ m.time }}</span>
            </div>
            <div class="pk-chat-bubble">{{ m.message }}</div>
          </div>
          <div v-if="chatMessages.length === 0" class="pk-chat-empty">{{ msg.chatEmpty }}</div>
        </div>
        <div class="pk-chat-input-row">
          <input
            v-model="chatInput"
            class="pk-chat-input"
            :placeholder="msg.chatPlaceholder"
            maxlength="500"
            @keydown.enter.prevent="sendChatMessage"
            :disabled="chatSending"
          >
          <button class="pk-chat-send" @click="sendChatMessage" :disabled="chatSending || !chatInput.trim()">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
      </div>
    </div>

  </div><!-- /poker-room -->

  <!-- Loading overlay (fixed, covers full screen during any pending request) -->
  <div v-if="loading" class="pk-loading-overlay">
    <div class="pk-loading-box">
      <div class="spinner-border pk-spinner" role="status"></div>
    </div>
  </div>
</template>

<script>
export default {
    name: 'PokerRoom',

    props: {
        table:         { type: Object, required: true },
        myUserId:      { type: Number, required: true },
        initialZCoins: { type: Number, default: 0 },
        routes:        { type: Object, required: true },
        msg:           { type: Object, required: true },
        csrf:          { type: String, required: true },
    },

    data() {
        return {
            // Loading overlay
            loading: true,

            // Section switch
            inGame: false,

            // Lobby
            lobbyLoading:  true,
            lobbyPlayers:  [],
            lobbyMsg:      '',
            myIsReady:     false,
            readyLoading:  false,

            // Game
            gameState:   null,
            winnerInfo:  null,
            actionLog:   [],
            zCoins:      this.initialZCoins,

            // Turn timer
            showTimer:   false,
            timerSec:    60,
            timerPct:    100,
            timerDanger: false,

            // Raise row
            showRaiseRow: false,
            raiseValue:   0,
            raiseMin:     0,
            raiseMax:     10000,
            raiseStep:    100,

            // Chat
            chatMessages: [],
            chatInput:    '',
            chatSending:  false,

            // Lobby countdown after showdown
            lobbyCountdown: 5,
        };
    },

    computed: {
        phase() {
            return this.gameState ? this.gameState.phase : 'waiting';
        },
        isMyTurn() {
            return this.gameState ? !!this.gameState.is_my_turn : false;
        },
        canCheck() {
            return this.gameState ? !!this.gameState.can_check : false;
        },
        canCall() {
            return this.gameState ? !!this.gameState.can_call : false;
        },
        toCall() {
            return this.gameState ? (this.gameState.to_call || 0) : 0;
        },
        myPlayer() {
            if (!this.gameState) return null;
            return this.gameState.players[this.gameState.my_index] || null;
        },
        canRaise() {
            const p = this.myPlayer;
            if (!p) return false;
            return p.chips > 0 && (p.chips + p.bet) > (this.gameState.min_raise || 0);
        },
        canAllin() {
            const p = this.myPlayer;
            return p ? p.chips > 0 : false;
        },
        callAmountText() {
            return this.fmtChips(this.toCall);
        },
        phaseLabel() {
            const labels = {
                preflop: 'PRE-FLOP', flop: 'FLOP',
                turn: 'TURN', river: 'RIVER', showdown: 'SHOWDOWN',
            };
            return labels[this.phase] || (this.phase || '').toUpperCase();
        },
        communityCards() {
            const cc = this.gameState ? (this.gameState.community_cards || []) : [];
            return Array.from({ length: 5 }, (_, i) => cc[i] || null);
        },
        potText() {
            const pot = this.gameState ? (this.gameState.pot || 0) : 0;
            return pot > 0 ? `${this.msg.potDisplay} ${this.fmtChips(pot)}` : '';
        },
        seatData() {
            const empty = Array.from({ length: 6 }, (_, i) => ({
                visible: false, index: i, name: '—', nameHtml: '', chips: '',
                bet: '', cards: [], posHtml: '', folded: false, activeTurn: false,
                isAI: false, isCurrent: false, isWinner: false, status: '', handName: '',
            }));
            if (!this.gameState) return empty;
            const s = this.gameState;
            s.players.forEach(p => {
                if (p.index < 0 || p.index > 5) return;
                const seat     = empty[p.index];
                seat.visible   = true;
                seat.name      = p.name;
                seat.chips     = this.fmtChips(p.chips);
                seat.bet       = p.bet > 0 ? `${this.msg.betLabel} ${this.fmtChips(p.bet)}` : '';
                seat.folded    = p.status === 'folded';
                seat.activeTurn = !!p.is_current && s.phase !== 'showdown';
                seat.isAI      = !!p.is_ai;
                seat.isCurrent = !!p.is_current;
                seat.isWinner  = !!p.is_winner;
                seat.status    = p.status || '';
                seat.handName  = p.hand_name || '';
                seat.cards     = p.hole_cards || [];
                let badges = '';
                if (p.is_dealer) badges += '<span class="badge-pos badge-d">D</span>';
                if (p.is_sb)     badges += '<span class="badge-pos badge-sb">SB</span>';
                if (p.is_bb)     badges += '<span class="badge-pos badge-bb">BB</span>';
                seat.posHtml   = badges;
                seat.nameHtml  = p.index === 0
                    ? this.escHtml(p.name)
                    : badges + this.escHtml(p.name);
            });
            return empty;
        },
        mySeat() {
            const idx = this.gameState ? (this.gameState.my_index ?? 0) : 0;
            return this.seatData[idx] || this.seatData[0];
        },
        aiSeats() {
            const idx = this.gameState ? (this.gameState.my_index ?? 0) : 0;
            return this.seatData.filter((_, i) => i !== idx);
        },
        winnerNames() {
            return this.winnerInfo ? this.winnerInfo.names.join(' & ') : '';
        },
        winnerDesc() {
            if (!this.winnerInfo) return '';
            const hand = this.winnerInfo.folded_win
                ? this.msg.opponentsFolded
                : `${this.msg.handLabel} <strong>${this.escHtml(this.winnerInfo.hand_name)}</strong>`;
            return `${hand} &mdash; ${this.msg.potLabel} <strong>${this.fmtChips(this.winnerInfo.pot)}</strong>`;
        },
        reversedLog() {
            return (this.actionLog || []).slice().reverse();
        },
    },

    mounted() {
        this._intentionalLeave = false;
        this.loadState();
        this.loadChatMessages();

        if (typeof window.initEcho === 'function') window.initEcho();
        if (window.Echo) {
            window.Echo.channel(`poker.room.${this.table.id}`)
                .listen('PokerRoomUpdated', this.onRoomEvent);
        }

        window.addEventListener('beforeunload', this.onBeforeUnload);
    },

    beforeUnmount() {
        window.removeEventListener('beforeunload', this.onBeforeUnload);
        if (window.Echo) window.Echo.leave(`poker.room.${this.table.id}`);
        this.stopPoll();
        if (this._turnInterval) clearInterval(this._turnInterval);
    },

    methods: {
        // ── Helpers ────────────────────────────────────────────────────────
        suitSym(suit) {
            return { spades: '♠', hearts: '♥', diamonds: '♦', clubs: '♣' }[suit] || '?';
        },
        suitColor(suit) {
            return ['hearts', 'diamonds'].includes(suit) ? 'red' : 'black';
        },
        fmtChips(n) {
            if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
            if (n >= 1000)    return (n / 1000).toFixed(1) + 'k';
            return Number(n).toLocaleString();
        },
        fmt(n) {
            return Number(n).toLocaleString();
        },
        escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        },

        // ── State loading ───────────────────────────────────────────────────
        loadState() {
            this.loading = true;
            fetch(this.routes.state, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(res => {
                    if (res.state) {
                        this.inGame = true;
                        this.render(res.state);
                    } else {
                        this.inGame       = false;
                        this.lobbyLoading = false;
                        if (res.players) this.updateLobby(res.players);
                    }
                })
                .catch(() => window.notify && window.notify('error', this.msg.connectionError))
                .finally(() => { this.loading = false; });
        },

        // ── Lobby ───────────────────────────────────────────────────────────
        updateLobby(players) {
            this.lobbyLoading = false;
            this.lobbyPlayers = players;
            const readyCount  = players.filter(p => p.is_ready).length;
            this.lobbyMsg     = (this.msg.playersReady || '')
                .replace(':ready', readyCount)
                .replace(':total', players.length);
            const me         = players.find(p => p.id === this.myUserId);
            this.myIsReady   = me ? !!me.is_ready : false;
        },

        toggleReady() {
            this.readyLoading = true;
            this.loading = true;
            fetch(this.routes.ready, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body:    JSON.stringify({}),
            })
                .then(r => r.json())
                .then(res => {
                    if (res.game_started) {
                        this.inGame = true;
                        this.render(res.state);
                    } else {
                        if (res.error) window.notify && window.notify('warning', res.error);
                        if (res.players) this.updateLobby(res.players);
                    }
                })
                .catch(() => window.notify && window.notify('error', this.msg.connectionError))
                .finally(() => { this.readyLoading = false; this.loading = false; });
        },

        // ── Game actions ────────────────────────────────────────────────────
        dealHand() {
            this.loading = true;
            fetch(this.routes.start, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body:    JSON.stringify({}),
            })
                .then(r => r.json())
                .then(res => { this.render(res.state); })
                .catch(() => window.notify && window.notify('error', this.msg.cantStartHand))
                .finally(() => { this.loading = false; });
        },

        sendAction(action, amount) {
            this.loading = true;
            fetch(this.routes.action, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body:    JSON.stringify({ action, amount: amount || 0 }),
            })
                .then(r => r.json())
                .then(res => { this.render(res.state); })
                .catch(() => window.notify && window.notify('error', this.msg.connectionError))
                .finally(() => { this.loading = false; });
        },

        doRaise() {
            if (!this.gameState || !this.myPlayer) return;
            const p       = this.myPlayer;
            const max     = p.chips + p.bet;
            this.raiseMin = this.gameState.min_raise || 0;
            this.raiseMax = max;
            this.raiseStep = this.gameState.big_blind || 100;
            this.raiseValue = this.raiseMin;
            this.showRaiseRow = true;
        },

        confirmRaise() {
            const val = parseInt(this.raiseValue, 10);
            this.showRaiseRow = false;
            this.sendAction('raise', val);
        },

        // ── Render ──────────────────────────────────────────────────────────
        render(s) {
            this.gameState   = s;
            this.winnerInfo  = s.winner_info || null;
            this.actionLog   = s.log || [];
            if (s.z_coins !== undefined) {
                this.zCoins = s.z_coins;
                const navEl = document.getElementById('nav-zcoin-balance');
                if (navEl) navEl.textContent = s.z_coins.toLocaleString();
            }
            this.renderTurnTimer(s);

            if (s.phase === 'showdown') {
                this.startLobbyTimer();
                this.stopPoll();
                return;
            }

            if (!window.Echo && !s.is_my_turn) {
                this.startPoll();
            } else {
                this.stopPoll();
            }
        },

        startLobbyTimer() {
            if (this._lobbyTimer) return; // already running
            this.lobbyCountdown = 5;
            this._lobbyTimer = setInterval(() => {
                this.lobbyCountdown--;
                if (this.lobbyCountdown <= 0) {
                    clearInterval(this._lobbyTimer);
                    this._lobbyTimer = null;
                    this.inGame    = false;
                    this.gameState = null;
                    this.loadState();
                }
            }, 1000);
        },

        renderTurnTimer(s) {
            if (this._turnInterval) {
                clearInterval(this._turnInterval);
                this._turnInterval = null;
            }

            if (!s.is_my_turn || !s.turn_started_at || s.phase === 'showdown') {
                this.showTimer = false;
                return;
            }

            const LIMIT    = 60;
            this.showTimer = true;

            const tick = () => {
                const elapsed    = Math.floor(Date.now() / 1000) - s.turn_started_at;
                const remaining  = Math.max(0, LIMIT - elapsed);
                this.timerSec    = remaining;
                this.timerPct    = (remaining / LIMIT) * 100;
                this.timerDanger = remaining <= 15;
            };
            tick();
            this._turnInterval = setInterval(tick, 500);
        },

        startPoll() {
            if (this._pollTimer) return;
            this._pollTimer = setInterval(() => {
                if (this._pollXhr) return;
                this._pollXhr = fetch(this.routes.state, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r => r.json())
                    .then(res => {
                        if (res.state) {
                            this.render(res.state);
                            if (res.state.is_my_turn || res.state.phase === 'showdown') {
                                this.stopPoll();
                            }
                        }
                    })
                    .finally(() => { this._pollXhr = null; });
            }, 3000);
        },

        stopPoll() {
            if (this._pollTimer) { clearInterval(this._pollTimer); this._pollTimer = null; }
            this._pollXhr = null;
        },

        // ── WebSocket events ────────────────────────────────────────────────
        onRoomEvent(e) {
            if (e.type === 'ready_update') {
                this.updateLobby(e.players);
            } else if (e.type === 'game_started') {
                const myState = e.seats && e.seats[this.myUserId];
                if (myState) {
                    if (this._lobbyTimer) { clearInterval(this._lobbyTimer); this._lobbyTimer = null; }
                    this.inGame = true;
                    this.render(myState);
                }
            } else if (e.type === 'game_update') {
                const myState = e.seats && e.seats[this.myUserId];
                if (myState) this.render(myState);
            } else if (e.type === 'refresh') {
                this.loadState();
            } else if (e.type === 'chat_message' && e.chat_message) {
                this.pushChatMessage(e.chat_message);
            }
        },

        onBeforeUnload() {
            if (window.Echo) window.Echo.leave(`poker.room.${this.table.id}`);
            this.stopPoll();
            if (this._turnInterval) clearInterval(this._turnInterval);
            if (this._lobbyTimer) { clearInterval(this._lobbyTimer); this._lobbyTimer = null; }

            if (!this._intentionalLeave) {
                fetch(this.routes.leave, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: '_method=DELETE',
                });
            }
        },

        confirmLeave() {
            this._intentionalLeave = true;
            if (window.confirmDialog) {
                window.confirmDialog(this.msg.confirmLeave, () => this.$refs.leaveForm.submit());
            } else {
                this.$refs.leaveForm.submit();
            }
        },

        // ── Chat ───────────────────────────────────────────────────────────
        loadChatMessages() {
            fetch(this.routes.chatMessages)
                .then(r => r.json())
                .then(data => {
                    this.chatMessages = data.messages || [];
                    this.$nextTick(() => this.scrollChatToBottom());
                });
        },
        sendChatMessage() {
            const text = this.chatInput.trim();
            if (!text) return;
            this.chatSending = true;
            this.chatInput   = '';
            fetch(this.routes.chatSend, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body:    JSON.stringify({ message: text }),
            })
                .then(r => r.json())
                .then(data => {
                    if (data.error) window.notify && window.notify('error', data.error);
                })
                .catch(() => window.notify && window.notify('error', 'Không thể gửi tin nhắn.'))
                .finally(() => { this.chatSending = false; });
        },
        pushChatMessage(msg) {
            this.chatMessages.push(msg);
            if (this.chatMessages.length > 200) this.chatMessages.shift();
            this.$nextTick(() => this.scrollChatToBottom());
        },
        scrollChatToBottom() {
            const box = this.$refs.chatBox;
            if (box) box.scrollTop = box.scrollHeight;
        },
    },
};
</script>

<style scoped>
.pk-need-players-notice {
    text-align: center;
    font-size: 12px;
    letter-spacing: 1px;
    color: #f6c23e;
    background: rgba(246, 194, 62, 0.08);
    border: 1px solid rgba(246, 194, 62, 0.25);
    border-radius: 8px;
    padding: 8px 14px;
}
/* ── Chat ───────────────────────────────────────────────── */
.pk-chat-panel {
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.pk-chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 8px 4px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-height: 100px;
  max-height: 150px;
}
.pk-chat-empty { color: #6c757d; font-size: 11px; text-align: center; margin: auto; }
.pk-chat-msg { display: flex; flex-direction: column; gap: 2px; max-width: 90%; }
.pk-chat-msg.pk-chat-mine { align-self: flex-end; align-items: flex-end; }
.pk-chat-meta { display: flex; gap: 5px; align-items: baseline; }
.pk-chat-name { font-size: 10px; font-weight: 700; color: #63b3ed; }
.pk-chat-mine .pk-chat-name { color: #f6c23e; }
.pk-chat-time { font-size: 9px; color: #6c757d; }
.pk-chat-bubble {
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 8px;
  padding: 4px 8px;
  font-size: 12px;
  color: #ddd;
  word-break: break-word;
}
.pk-chat-mine .pk-chat-bubble {
  background: rgba(246,194,62,.1);
  border-color: rgba(246,194,62,.2);
}
.pk-chat-input-row {
  display: flex;
  border-top: 1px solid rgba(255,255,255,.08);
  margin-top: 4px;
}
.pk-chat-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #ddd;
  padding: 6px 8px;
  font-size: 12px;
}
.pk-chat-input::placeholder { color: #6c757d; font-size: 11px; }
.pk-chat-send {
  background: transparent;
  border: none;
  border-left: 1px solid rgba(255,255,255,.08);
  color: #f6c23e;
  padding: 6px 10px;
  cursor: pointer;
  font-size: 12px;
  transition: background .15s;
}
.pk-chat-send:hover:not(:disabled) { background: rgba(246,194,62,.1); }
.pk-chat-send:disabled { color: #555; cursor: default; }

.pk-loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(8, 15, 26, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
}
.pk-loading-box {
    background: rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(246, 194, 62, 0.3);
    border-radius: 16px;
    padding: 28px 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.pk-spinner {
    width: 2.8rem;
    height: 2.8rem;
    border-width: 3px;
    color: #f6c23e;
}
</style>
