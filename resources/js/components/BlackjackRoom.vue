<template>
  <div class="bj-room">

    <!-- Loading overlay -->
    <div v-if="loading" class="bj-loading-overlay">
      <div class="bj-loading-box">
        <div class="spinner-border bj-spinner" role="status"></div>
      </div>
    </div>

    <!-- Hidden leave form -->
    <form ref="leaveForm" :action="routes.leave" method="POST" style="display:none;">
      <input type="hidden" name="_token" :value="csrf">
      <input type="hidden" name="_method" value="DELETE">
    </form>

    <!-- Header -->
    <div class="bj-header">
      <button class="btn btn-sm btn-secondary" @click="leaveTable">
        <i class="fas fa-sign-out-alt mr-1"></i>{{ msg.leaveTable }}
      </button>
      <div class="text-center">
        <span class="bj-header-title">{{ table.name.toUpperCase() }}</span>
        <div style="font-size:11px;color:#6c757d;letter-spacing:1px;">
          {{ fmt(table.min_bet) }}–{{ fmt(table.max_bet) }} Zoo
        </div>
      </div>
      <span>
        <i class="fas fa-coins mr-1" style="color:#f6c23e;"></i>
        <span class="font-weight-bold" style="color:#f6c23e;">{{ fmt(balance) }}</span>
        <small class="text-muted ml-1">Zoo</small>
      </span>
    </div>

    <!-- ── LOBBY ─────────────────────────────────────────────────── -->
    <div v-if="phase === 'lobby'" class="bj-lobby">

      <!-- Countdown overlay -->
      <div v-if="countdownSec !== null" class="bj-countdown-overlay">
        <div class="bj-countdown-box">
          <div class="bj-countdown-label">{{ msg.startingIn }}</div>
          <div class="bj-countdown-num" :key="countdownSec">{{ countdownSec }}</div>
        </div>
      </div>

      <div class="bj-lobby-inner">
        <h5 class="bj-lobby-title">
          <i class="fas fa-hourglass-half mr-2"></i>{{ msg.waitingRoom }}
        </h5>

        <!-- Role selection -->
        <div v-if="!currentRole || currentRole === ''" class="bj-role-select mb-3">
          <p class="text-muted text-center mb-2" style="font-size:13px;">Chọn vai trò của bạn</p>
          <div class="d-flex justify-content-center gap-2">
            <button class="btn btn-outline-warning btn-sm mr-2"
                    :disabled="dealerTaken"
                    @click="chooseRole('dealer', 0)">
              <i class="fas fa-crown mr-1"></i> Nhà cái (Dealer)
              <span v-if="dealerTaken" class="ml-1 text-danger">(Đã có)</span>
            </button>
            <button class="btn btn-outline-info btn-sm" @click="showSeatModal = true">
              <i class="fas fa-chair mr-1"></i> Nhà con (Player)
            </button>
          </div>
        </div>

        <!-- Current role badge -->
        <div v-else class="text-center mb-2">
          <span v-if="currentRole === 'dealer'" class="badge badge-warning px-3 py-2">
            <i class="fas fa-crown mr-1"></i> Nhà cái
          </span>
          <span v-else class="badge badge-info px-3 py-2">
            <i class="fas fa-chair mr-1"></i> Nhà con – Ghế {{ currentSeat }}
          </span>
          <button class="btn btn-link btn-sm text-muted ml-2" @click="resetRole">Đổi</button>
        </div>

        <!-- Player list -->
        <div class="bj-lobby-players">
          <!-- Dealer slot -->
          <div class="bj-lobby-player bj-dealer-slot">
            <i class="fas fa-crown mr-2" style="color:#f6c23e;"></i>
            <span class="bj-lobby-name">
              <template v-if="dealerPlayer">{{ dealerPlayer.name }}</template>
              <template v-else><em class="text-muted">Chưa có nhà cái</em></template>
            </span>
            <span v-if="dealerPlayer" :class="['badge ml-auto', dealerPlayer.is_ready ? 'badge-success' : 'badge-secondary']">
              {{ dealerPlayer.is_ready ? msg.ready : msg.waiting }}
            </span>
          </div>
          <!-- Player seats 1-7 -->
          <div v-for="n in 7" :key="n" class="bj-lobby-player">
            <span class="bj-seat-num mr-2">{{ n }}</span>
            <template v-if="playerAtSeat(n)">
              <span :class="['bj-ready-dot', playerAtSeat(n).is_ready ? 'is-ready' : '']"></span>
              <span class="bj-lobby-name">{{ playerAtSeat(n).name }}</span>
              <span :class="['badge ml-auto', playerAtSeat(n).is_ready ? 'badge-success' : 'badge-secondary']">
                {{ playerAtSeat(n).is_ready ? msg.ready : msg.waiting }}
              </span>
            </template>
            <template v-else>
              <span class="text-muted" style="font-size:12px;">Trống</span>
            </template>
          </div>
        </div>

        <!-- Start condition info -->
        <div class="bj-start-condition mt-2">
          <span :class="dealerReady ? 'text-success' : 'text-warning'">
            <i :class="['fas mr-1', dealerReady ? 'fa-check-circle' : 'fa-exclamation-circle']"></i>
            {{ dealerReady ? 'Nhà cái sẵn sàng' : 'Cần nhà cái sẵn sàng' }}
          </span>
          <span class="mx-2 text-muted">·</span>
          <span :class="readyPlayerCount >= 1 ? 'text-success' : 'text-warning'">
            <i :class="['fas mr-1', readyPlayerCount >= 1 ? 'fa-check-circle' : 'fa-exclamation-circle']"></i>
            {{ readyPlayerCount }} nhà con sẵn sàng
          </span>
        </div>

        <!-- Ready button -->
        <div class="text-center mt-3">
          <button
            :class="['btn btn-lg px-5', myIsReady ? 'btn-secondary' : 'btn-success']"
            :disabled="readyLoading || countdownSec !== null || !currentRole"
            @click="toggleReady">
            <i :class="['fas mr-1', myIsReady ? 'fa-times' : 'fa-check']"></i>
            {{ myIsReady ? msg.cancelReady : msg.imReady }}
          </button>
        </div>

        <!-- Dealer start button -->
        <div v-if="currentRole === 'dealer' && dealerReady && readyPlayerCount >= 1 && countdownSec === null"
             class="text-center mt-2">
          <button class="btn btn-warning btn-sm" @click="startRound" :disabled="starting">
            <i class="fas fa-play mr-1"></i> Bắt đầu ngay
          </button>
        </div>
      </div>
    </div>

    <!-- ── GAME PHASES ────────────────────────────────────────────── -->
    <template v-else>

      <!-- Multi-seat felt -->
      <div class="bj-felt-wrap">
        <div class="bj-felt">

          <!-- Dealer area -->
          <div class="bj-area">
            <div class="bj-area-label">
              <i class="fas fa-crown mr-1" style="color:#f6c23e;"></i>
              Nhà cái{{ roundDealer ? ' – ' + roundDealer.name : '' }}
            </div>
            <div class="bj-cards-row">
              <div v-for="(card, i) in roundDealer.cards" :key="i" :class="cardClass(card)">
                <span class="cc-top">{{ cardLabel(card) }}</span>
                <span class="cc-mid">{{ suitSym(card.suit) }}</span>
                <span class="cc-bot">{{ cardLabel(card) }}</span>
              </div>
            </div>
            <span v-if="roundDealer.cards.length" :class="['bj-score-badge', dealerScoreClass]">
              {{ roundDealer.score }}
            </span>
          </div>

          <!-- Center info -->
          <div class="bj-center-info">
            <span v-if="phase === 'betting'" class="text-warning" style="font-size:13px;">
              <i class="fas fa-coins mr-1"></i> Đặt cược
            </span>
            <span v-else-if="phase === 'dealer_turn' && currentRole !== 'dealer'" class="text-info" style="font-size:13px;">
              <i class="fas fa-hourglass-half mr-1"></i> Chờ nhà cái rút bài...
            </span>
          </div>

          <!-- All player seats -->
          <div class="bj-seats-row">
            <div v-for="p in sortedPlayers" :key="p.user_id"
                 :class="['bj-seat', p.user_id === currentTurnUserId ? 'bj-active-seat' : '',
                           p.result ? 'bj-result-' + p.result : '']">
              <div class="bj-seat-header">
                <span class="bj-seat-num-badge">{{ p.seat }}</span>
                <span class="bj-seat-name">{{ p.name }}{{ p.user_id === myUserId ? ' (bạn)' : '' }}</span>
              </div>

              <!-- Turn indicator -->
              <div v-if="p.user_id === currentTurnUserId && phase === 'player_turns'"
                   class="bj-turn-indicator">
                <i class="fas fa-arrow-down bj-bounce"></i>
              </div>

              <!-- Cards -->
              <div class="bj-cards-row bj-seat-cards">
                <div v-for="(card, i) in p.cards" :key="i" :class="cardClass(card)">
                  <span class="cc-top">{{ cardLabel(card) }}</span>
                  <span class="cc-mid">{{ suitSym(card.suit) }}</span>
                  <span class="cc-bot">{{ cardLabel(card) }}</span>
                </div>
              </div>

              <!-- Score -->
              <span v-if="p.cards.length" :class="['bj-score-badge', playerScoreClass(p)]">
                {{ playerScore(p) }}
              </span>

              <!-- Bet -->
              <div v-if="p.bet" class="bj-seat-bet">
                <i class="fas fa-coins mr-1" style="color:#f6c23e;font-size:10px;"></i>
                {{ fmt(p.bet) }}
              </div>

              <!-- Result overlay -->
              <div v-if="p.result && phase === 'finished'" :class="['bj-result-text', p.result]">
                {{ resultLabel(p) }}
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ── Betting panel (for current user, player role) ── -->
      <div v-if="phase === 'betting' && currentRole === 'player' && !myBetPlaced" class="bj-action-panel">
        <div class="bj-chip-row">
          <button v-for="v in chips" :key="v"
                  :class="['bj-chip', currentBet === v ? 'active' : '']"
                  @click="currentBet = v">
            {{ fmtChip(v) }}
          </button>
        </div>
        <div class="bj-bet-input-wrap">
          <input id="bj-bet-input" type="number" v-model.number="currentBet"
                 :min="table.min_bet" :max="table.max_bet">
          <button class="btn btn-success px-4" :disabled="acting" @click="placeBet">
            <i class="fas fa-coins mr-1"></i> Đặt cược
          </button>
        </div>
      </div>
      <div v-else-if="phase === 'betting' && currentRole === 'player' && myBetPlaced" class="text-center py-3 text-muted">
        <i class="fas fa-check-circle text-success mr-2"></i> Đã đặt cược – chờ người chơi khác...
      </div>

      <!-- ── Action panel (my turn) ── -->
      <div v-if="phase === 'player_turns' && currentTurnUserId === myUserId" class="bj-action-panel">
        <div class="bj-action-btns">
          <button class="btn btn-success" :disabled="acting" @click="doAction('hit')">
            <i class="fas fa-plus mr-1"></i>{{ msg.hit }}
          </button>
          <button class="btn btn-warning" :disabled="acting" @click="doAction('stand')">
            <i class="fas fa-hand-paper mr-1"></i>{{ msg.stand }}
          </button>
          <button v-if="myPlayerState && myPlayerState.can_double"
                  class="btn btn-info" :disabled="acting" @click="doAction('double')">
            <i class="fas fa-layer-group mr-1"></i>{{ msg.double }}
          </button>
        </div>
      </div>
      <div v-else-if="phase === 'player_turns' && currentTurnUserId !== myUserId && currentRole === 'player'"
           class="text-center py-3 text-muted">
        <i class="fas fa-clock mr-2"></i> Chờ lượt của bạn...
      </div>

      <!-- ── Dealer controls ── -->
      <div v-if="currentRole === 'dealer'" class="text-center py-3">
        <span v-if="phase === 'betting'" class="text-muted">
          <i class="fas fa-info-circle mr-1"></i> Chờ tất cả nhà con đặt cược...
        </span>
        <span v-else-if="phase === 'player_turns'" class="text-muted">
          <i class="fas fa-clock mr-1"></i> Nhà con đang chơi...
        </span>
        <div v-else-if="phase === 'dealer_turn'" class="bj-action-panel">
          <div class="bj-action-btns">
            <button class="btn btn-success" :disabled="acting || roundDealer.score >= 17"
                    @click="doDealerAction('hit')">
              <i class="fas fa-plus mr-1"></i> Rút bài
            </button>
            <button class="btn btn-warning" :disabled="acting || roundDealer.score < 17"
                    @click="doDealerAction('stand')">
              <i class="fas fa-hand-paper mr-1"></i> Dừng
            </button>
          </div>
          <div class="text-muted" style="font-size:12px;">
            <template v-if="roundDealer.score < 17">Điểm &lt; 17 — phải rút bài</template>
            <template v-else>Điểm ≥ 17 — nhấn Dừng để kết thúc</template>
          </div>
        </div>
        <button v-if="phase === 'finished'" class="btn btn-warning px-5" @click="nextRound" :disabled="starting">
          <i class="fas fa-redo mr-1"></i> Ván tiếp theo
        </button>
      </div>

    </template>

    <!-- ── Chat panel ── -->
    <div class="bj-chat-panel">
      <div class="bj-chat-header">
        <i class="fas fa-comments mr-1"></i> Chat
      </div>
      <div class="bj-chat-messages" ref="chatBox">
        <div v-for="m in chatMessages" :key="m.id"
             :class="['bj-chat-msg', m.user_id === myUserId ? 'bj-chat-mine' : '']">
          <div class="bj-chat-meta">
            <span class="bj-chat-name">{{ m.user_id === myUserId ? 'Bạn' : m.name }}</span>
            <span class="bj-chat-time">{{ m.time }}</span>
          </div>
          <div class="bj-chat-bubble">{{ m.message }}</div>
        </div>
        <div v-if="chatMessages.length === 0" class="bj-chat-empty">
          Chưa có tin nhắn nào...
        </div>
      </div>
      <div class="bj-chat-input-row">
        <input
          v-model="chatInput"
          class="bj-chat-input"
          placeholder="Nhập tin nhắn..."
          maxlength="500"
          @keydown.enter.prevent="sendChatMessage"
          :disabled="chatSending"
        >
        <button class="bj-chat-send" @click="sendChatMessage" :disabled="chatSending || !chatInput.trim()">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
    </div>

    <!-- ── Seat selection modal ── -->
    <div v-if="showSeatModal" class="bj-modal-backdrop" @click.self="showSeatModal = false">
      <div class="modal-dialog mt-5">
        <div class="modal-content" style="background:#1a2332;border:1px solid #2d3f55;">
          <div class="modal-header border-secondary">
            <h5 class="modal-title"><i class="fas fa-chair mr-2"></i>Chọn ghế ngồi</h5>
            <button type="button" class="close text-white" @click="showSeatModal = false">&times;</button>
          </div>
          <div class="modal-body">
            <div class="bj-seat-grid">
              <button v-for="n in 7" :key="n"
                      :class="['bj-seat-btn', seatTaken(n) ? 'taken' : '', mySeat === n ? 'active' : '']"
                      :disabled="seatTaken(n)"
                      @click="chooseRole('player', n); showSeatModal = false">
                <i class="fas fa-chair mr-1"></i> Ghế {{ n }}
                <span v-if="seatTaken(n)" class="d-block" style="font-size:10px;">{{ playerAtSeat(n)?.name }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  name: 'BlackjackRoom',

  props: {
    table:     { type: Object,  required: true },
    myUserId:  { type: Number,  required: true },
    myRole:    { type: String,  default: '' },
    mySeat:    { type: Number,  default: null },
    routes:    { type: Object,  required: true },
    msg:       { type: Object,  required: true },
    csrf:      { type: String,  required: true },
  },

  data() {
    return {
      // Lobby
      phase:            'lobby',
      lobbyPlayers:     [],
      myIsReady:        false,
      readyLoading:     false,
      countdownSec:     null,
      currentRole:      this.myRole || '',
      currentSeat:      this.mySeat || null,
      showSeatModal:    false,
      starting:         false,

      // Round state
      roundId:          null,
      roundDealer:      { cards: [], score: 0, name: '' },
      roundPlayers:     [],
      turnOrder:        [],
      currentTurnUserId: null,
      myBetPlaced:      false,

      // Actions
      currentBet:       this.table.min_bet,
      acting:           false,
      balance:          0,
      loading:          true,

      // Chat
      chatMessages:  [],
      chatInput:     '',
      chatSending:   false,
    };
  },

  computed: {
    dealerPlayer() {
      return this.lobbyPlayers.find(p => p.role === 'dealer') || null;
    },
    dealerReady() {
      return !!this.lobbyPlayers.find(p => p.role === 'dealer' && p.is_ready);
    },
    dealerTaken() {
      return this.lobbyPlayers.some(p => p.role === 'dealer' && p.id !== this.myUserId);
    },
    readyPlayerCount() {
      return this.lobbyPlayers.filter(p => p.role === 'player' && p.is_ready).length;
    },
    sortedPlayers() {
      return [...this.roundPlayers].sort((a, b) => a.seat - b.seat);
    },
    myPlayerState() {
      return this.roundPlayers.find(p => p.user_id === this.myUserId) || null;
    },
    chips() {
      const presets = [100, 500, 1000, 5000, 10000, 50000, 100000];
      return presets.filter(v => v >= this.table.min_bet && v <= this.table.max_bet);
    },
    dealerScoreClass() {
      if (this.phase !== 'finished') return '';
      const s = this.roundDealer.score;
      if (this.roundDealer.blackjack) return 'bj';
      if (s > 21) return 'bust';
      return '';
    },
  },

  watch: {
    balance(val) {
      const el = document.getElementById('nav-zcoin-balance');
      if (el) el.textContent = Number(val).toLocaleString();
    },
  },

  methods: {
    fmt(n) { return Number(n).toLocaleString(); },
    fmtChip(v) {
      if (v >= 1000000) return (v / 1000000).toFixed(1) + 'M';
      if (v >= 1000) return (v / 1000).toFixed(0) + 'k';
      return v;
    },
    suitSym(suit) {
      return { spades: '♠', hearts: '♥', diamonds: '♦', clubs: '♣' }[suit] || '';
    },
    cardLabel(card) {
      if (!card || card.suit === 'back') return '';
      return card.rank;
    },
    cardClass(card) {
      if (!card || card.suit === 'back') return ['play-card', 'card-back'];
      const color = ['hearts', 'diamonds'].includes(card.suit) ? 'red' : 'black';
      return ['play-card', color];
    },
    playerScore(p) {
      return p.cards.reduce((total, c) => {
        if (c.suit === 'back') return total;
        return total;
      }, 0);
      // Score is computed server-side; we'll just show server value from cards
    },
    playerScoreClass(p) {
      if (this.phase !== 'finished') return '';
      if (p.blackjack) return 'bj';
      if (p.busted) return 'bust';
      return '';
    },
    resultLabel(p) {
      const map = {
        blackjack:   'BLACKJACK!',
        win:         'THẮNG',
        dealer_bust: 'THẮNG',
        push:        'HÒA',
        bust:        'QUÁ 21',
        lose:        'THUA',
      };
      return map[p.result] || p.result;
    },
    playerAtSeat(n) {
      return this.lobbyPlayers.find(p => p.role === 'player' && p.seat === n) || null;
    },
    seatTaken(n) {
      return this.lobbyPlayers.some(p => p.role === 'player' && p.seat === n && p.id !== this.myUserId);
    },

    // ── Role/seat ──
    chooseRole(role, seat) {
      fetch(this.routes.role, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
        body: JSON.stringify({ role, seat }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) { if (window.notify) window.notify('error', data.error); return; }
          this.currentRole = role;
          this.currentSeat = seat;
          this.updateLobby(data.players);
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); });
    },
    resetRole() {
      this.currentRole = '';
      this.currentSeat = null;
    },

    // ── Lobby ──
    updateLobby(players) {
      this.lobbyPlayers = players || [];
      const me = players?.find(p => p.id === this.myUserId);
      if (me) {
        this.myIsReady   = me.is_ready;
        this.currentRole = me.role || '';
        this.currentSeat = me.seat || null;
      }
    },

    toggleReady() {
      this.readyLoading = true;
      fetch(this.routes.ready, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
      })
        .then(r => r.json())
        .then(data => {
          this.updateLobby(data.players);
          if (data.countdown_at) this.startCountdown(data.countdown_at);
          else this.clearCountdown();
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.readyLoading = false; });
    },

    startRound() {
      this.starting = true;
      fetch(this.routes.start, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) { if (window.notify) window.notify('error', data.error); return; }
          this.clearCountdown();
          this.applyRoundState(data.round);
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.starting = false; });
    },

    // ── Round state ──
    applyRoundState(s) {
      this.phase             = s.phase;
      this.roundId           = s.round_id;
      this.roundDealer       = s.dealer   || { cards: [], score: 0, name: '' };
      this.roundPlayers      = s.players  || [];
      this.turnOrder         = s.turn_order || [];
      this.currentTurnUserId = s.current_turn_user_id;
      if (s.my_balance !== undefined) this.balance = s.my_balance;

      const me = this.roundPlayers.find(p => p.user_id === this.myUserId);
      this.myBetPlaced = me ? me.bet_placed : false;
    },

    // ── Betting ──
    placeBet() {
      const bet = parseInt(this.currentBet, 10);
      if (!bet || bet < this.table.min_bet || bet > this.table.max_bet) {
        if (window.notify) window.notify('warning', 'Số tiền cược không hợp lệ.');
        return;
      }
      this.acting = true;
      fetch(this.routes.deal, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
        body: JSON.stringify({ bet }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) { if (window.notify) window.notify('error', data.error); return; }
          this.applyRoundState(data.round);
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.acting = false; });
    },

    // ── Actions ──
    doAction(action) {
      this.acting = true;
      fetch(this.routes.action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
        body: JSON.stringify({ action }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) { if (window.notify) window.notify('error', data.error); return; }
          this.applyRoundState(data.round);
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.acting = false; });
    },

    // ── Dealer turn ──
    doDealerAction(action) {
      this.acting = true;
      fetch(this.routes.dealerAction, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
        body: JSON.stringify({ action }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) { if (window.notify) window.notify('error', data.error); return; }
          this.applyRoundState(data.round);
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.acting = false; });
    },

    nextRound() {
      this.starting = true;
      fetch(this.routes.next, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) { if (window.notify) window.notify('error', data.error); return; }
          this.applyRoundState(data.round);
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.starting = false; });
    },

    // ── WebSocket ──
    onRoomEvent(e) {
      if (e.type === 'ready_update') {
        this.updateLobby(e.players);
      } else if (e.type === 'countdown_start') {
        this.updateLobby(e.players);
        this.startCountdown(e.countdown_at);
      } else if (e.type === 'countdown_cancel') {
        this.clearCountdown();
        this.updateLobby(e.players);
      } else if (['round_started', 'bet_placed', 'cards_dealt', 'player_acted', 'dealer_turn', 'round_finished'].includes(e.type)) {
        if (e.round_state && Object.keys(e.round_state).length) {
          this.applyRoundState(e.round_state);
        }
        if (e.type === 'round_started') this.phase = 'betting';
      } else if (e.type === 'chat_message' && e.chat_message) {
        this.pushChatMessage(e.chat_message);
      }
    },

    startCountdown(ts) {
      this.clearCountdown();
      const tick = () => {
        const sec = Math.max(0, ts - Math.floor(Date.now() / 1000));
        this.countdownSec = sec;
        if (sec > 0) {
          this._countdownTimer = setTimeout(tick, 1000);
        } else {
          this._countdownTimer = null;
          this.countdownSec   = null;
        }
      };
      tick();
    },
    clearCountdown() {
      if (this._countdownTimer) { clearTimeout(this._countdownTimer); this._countdownTimer = null; }
      this.countdownSec = null;
    },

    // ── Chat ──
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
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
        body:    JSON.stringify({ message: text }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) { if (window.notify) window.notify('error', data.error); }
        })
        .catch(() => { if (window.notify) window.notify('error', 'Không thể gửi tin nhắn.'); })
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

    // ── Leave ──
    leaveTable() {
      if (window.confirmDialog) {
        window.confirmDialog(this.msg.confirmLeave, () => {
          this._intentionalLeave = true;
          this.$refs.leaveForm.submit();
        });
      } else {
        this._intentionalLeave = true;
        this.$refs.leaveForm.submit();
      }
    },
    _sendLeaveBeacon() {
      if (this._intentionalLeave) return;
      fetch(this.routes.leave, {
        method: 'POST',
        keepalive: true,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': this.csrf },
        body: '_method=DELETE',
      });
    },
  },

  mounted() {
    this._intentionalLeave = false;
    this._onBeforeUnload   = () => this._sendLeaveBeacon();
    window.addEventListener('beforeunload', this._onBeforeUnload);

    // Load initial state
    this.loading = true;
    fetch(this.routes.state)
      .then(r => r.json())
      .then(data => {
        if (data.z_coins !== undefined) this.balance = data.z_coins;
        if (data.round) {
          this.applyRoundState(data.round);
        } else {
          this.phase = 'lobby';
          this.updateLobby(data.players || []);
          if (data.countdown_at) this.startCountdown(data.countdown_at);
        }
      })
      .finally(() => { this.loading = false; });

    this.loadChatMessages();

    if (typeof window.initEcho === 'function') window.initEcho();
    if (window.Echo) {
      window.Echo.channel(`blackjack.room.${this.table.id}`)
        .listen('BlackjackRoomUpdated', this.onRoomEvent);
    }
  },

  beforeUnmount() {
    window.removeEventListener('beforeunload', this._onBeforeUnload);
    if (window.Echo) window.Echo.leave(`blackjack.room.${this.table.id}`);
    this.clearCountdown();
  },
};
</script>

<style scoped>
/* ── Lobby ─────────────────────────────────────────── */
.bj-lobby {
  display: flex;
  justify-content: center;
  padding: 20px 0;
}
.bj-lobby-inner {
  width: 100%;
  max-width: 520px;
  background: rgba(255,255,255,.04);
  border: 1px solid #2d3f55;
  border-radius: 12px;
  padding: 24px;
}
.bj-lobby-title {
  color: #e0e0e0;
  text-align: center;
  margin-bottom: 20px;
}
.bj-lobby-players {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 12px;
}
.bj-lobby-player {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 8px;
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.06);
}
.bj-dealer-slot {
  border-color: rgba(246,194,62,.3);
  background: rgba(246,194,62,.04);
}
.bj-ready-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: #6c757d;
  margin-right: 8px;
  flex-shrink: 0;
}
.bj-ready-dot.is-ready { background: #2ecc71; }
.bj-lobby-name { font-size: 14px; color: #c9d1d9; }
.bj-seat-num { font-size: 11px; color: #6c757d; width: 18px; text-align: center; }
.bj-start-condition {
  font-size: 12px;
  text-align: center;
  padding: 6px 0;
}

/* ── Role select ─────────────────────────────────── */
.bj-role-select button { font-size: 13px; }

/* ── Seat grid ───────────────────────────────────── */
.bj-seat-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}
.bj-seat-btn {
  padding: 10px 6px;
  border: 1px solid #2d3f55;
  border-radius: 8px;
  background: rgba(255,255,255,.04);
  color: #e0e0e0;
  font-size: 13px;
  cursor: pointer;
  transition: background .15s;
}
.bj-seat-btn:hover:not(:disabled) { background: rgba(99,179,237,.15); border-color: #63b3ed; }
.bj-seat-btn.active { background: rgba(99,179,237,.2); border-color: #63b3ed; color: #63b3ed; }
.bj-seat-btn.taken  { opacity: .5; cursor: not-allowed; }

/* ── Countdown ───────────────────────────────────── */
.bj-countdown-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,.75);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  z-index: 10;
}
.bj-countdown-box { text-align: center; }
.bj-countdown-label { font-size: 14px; color: rgba(255,255,255,.6); letter-spacing: 2px; text-transform: uppercase; }
.bj-countdown-num { font-size: 72px; font-weight: 900; color: #f6c23e; line-height: 1; }

/* ── Multi-seat felt ─────────────────────────────── */
.bj-seats-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 8px;
  width: 100%;
}
.bj-seat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  min-width: 80px;
  padding: 8px 6px;
  background: rgba(0,0,0,.25);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 10px;
  position: relative;
  transition: border-color .2s;
}
.bj-seat.bj-active-seat {
  border-color: #f6c23e;
  box-shadow: 0 0 10px rgba(246,194,62,.4);
}
.bj-seat-header {
  display: flex;
  align-items: center;
  gap: 4px;
}
.bj-seat-num-badge {
  background: rgba(255,255,255,.12);
  border-radius: 50%;
  width: 18px; height: 18px;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; color: #aaa;
  flex-shrink: 0;
}
.bj-seat-name { font-size: 11px; color: #c9d1d9; }
.bj-seat-cards { flex-wrap: nowrap; }
.bj-seat-bet { font-size: 11px; color: #f6c23e; }
.bj-turn-indicator {
  font-size: 11px;
  color: #f6c23e;
  animation: bjBounce .6s infinite alternate;
}
@keyframes bjBounce {
  from { transform: translateY(0); }
  to   { transform: translateY(-4px); }
}
.bj-result-text {
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1px;
  position: absolute;
  top: 4px; right: 4px;
}
.bj-result-text.win, .bj-result-text.dealer_bust, .bj-result-text.blackjack { color: #2ecc71; }
.bj-result-text.push { color: #bdc3c7; }
.bj-result-text.bust, .bj-result-text.lose { color: #e74c3c; }

/* ── Chat ────────────────────────────────────────── */
.bj-chat-panel {
  margin-top: 16px;
  border: 1px solid #2d3f55;
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  max-height: 280px;
}
.bj-chat-header {
  padding: 6px 12px;
  background: rgba(255,255,255,.04);
  border-bottom: 1px solid #2d3f55;
  font-size: 12px;
  font-weight: 600;
  color: #adb5bd;
  letter-spacing: 1px;
  text-transform: uppercase;
}
.bj-chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 8px 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-height: 160px;
  max-height: 200px;
}
.bj-chat-empty {
  color: #6c757d;
  font-size: 12px;
  text-align: center;
  margin: auto;
}
.bj-chat-msg {
  display: flex;
  flex-direction: column;
  gap: 2px;
  max-width: 85%;
}
.bj-chat-msg.bj-chat-mine {
  align-self: flex-end;
  align-items: flex-end;
}
.bj-chat-meta {
  display: flex;
  gap: 6px;
  align-items: baseline;
}
.bj-chat-name {
  font-size: 11px;
  font-weight: 600;
  color: #63b3ed;
}
.bj-chat-mine .bj-chat-name {
  color: #f6c23e;
}
.bj-chat-time {
  font-size: 10px;
  color: #6c757d;
}
.bj-chat-bubble {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 10px;
  padding: 5px 10px;
  font-size: 13px;
  color: #e0e0e0;
  word-break: break-word;
}
.bj-chat-mine .bj-chat-bubble {
  background: rgba(246,194,62,.12);
  border-color: rgba(246,194,62,.2);
}
.bj-chat-input-row {
  display: flex;
  gap: 0;
  border-top: 1px solid #2d3f55;
}
.bj-chat-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #e0e0e0;
  padding: 8px 12px;
  font-size: 13px;
}
.bj-chat-input::placeholder { color: #6c757d; }
.bj-chat-send {
  background: transparent;
  border: none;
  border-left: 1px solid #2d3f55;
  color: #f6c23e;
  padding: 8px 14px;
  cursor: pointer;
  transition: background .15s;
}
.bj-chat-send:hover:not(:disabled) { background: rgba(246,194,62,.1); }
.bj-chat-send:disabled { color: #6c757d; cursor: default; }

/* ── Loading ─────────────────────────────────────── */
.bj-loading-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.55);
  z-index: 9999;
  display: flex; align-items: center; justify-content: center;
}
.bj-loading-box { text-align: center; }
.bj-spinner { width: 3rem; height: 3rem; color: #f6c23e; }

/* ── Modal ───────────────────────────────────────── */
.bj-modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(0,0,0,.6);
  z-index: 1050;
  display: flex; align-items: flex-start; justify-content: center;
}
</style>
