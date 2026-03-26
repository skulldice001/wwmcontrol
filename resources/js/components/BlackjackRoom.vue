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

    <!-- Header (always visible) -->
    <div class="bj-header">
      <button class="btn btn-sm btn-secondary" @click="leaveTable">
        <i class="fas fa-sign-out-alt mr-1"></i>{{ msg.leaveTable }}
      </button>
      <div class="text-center">
        <span class="bj-header-title">{{ table.name.toUpperCase() }}</span>
        <div style="font-size:11px;color:#6c757d;letter-spacing:1px;">
          {{ fmt(table.min_bet) }}–{{ fmt(table.max_bet) }} Z &nbsp;·&nbsp;
          <span>{{ playerCount }}</span>/{{ table.max_players }} {{ msg.players || 'players' }}
        </div>
      </div>
      <span>
        <i class="fas fa-coins mr-1" style="color:#f6c23e;"></i>
        <span class="font-weight-bold" style="color:#f6c23e;">{{ fmt(balance) }}</span>
        <small class="text-muted ml-1">Z</small>
      </span>
    </div>

    <!-- ── Lobby waiting room ──────────────────────────────────────── -->
    <div v-if="inLobby" class="bj-lobby">

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

        <!-- Player list -->
        <div v-if="lobbyLoading" class="text-center py-3 text-muted">
          <div class="spinner-border spinner-border-sm mr-2" role="status"></div>
          {{ msg.loadingPlayers }}
        </div>
        <div v-else class="bj-lobby-players">
          <div v-for="p in lobbyPlayers" :key="p.id" class="bj-lobby-player">
            <span :class="['bj-ready-dot', p.is_ready ? 'is-ready' : '']"></span>
            <span class="bj-lobby-name">{{ p.name }}</span>
            <span :class="['badge ml-auto', p.is_ready ? 'badge-success' : 'badge-secondary']">
              {{ p.is_ready ? msg.ready : msg.waiting }}
            </span>
          </div>
        </div>

        <!-- Ready button -->
        <div class="text-center mt-4">
          <button
            :class="['btn btn-lg px-5', myIsReady ? 'btn-secondary' : 'btn-success']"
            :disabled="readyLoading || countdownSec !== null"
            @click="toggleReady"
          >
            <i :class="['fas mr-1', myIsReady ? 'fa-times' : 'fa-check']"></i>
            {{ myIsReady ? msg.cancelReady : msg.imReady }}
          </button>
        </div>

        <div class="text-center mt-2 text-muted" style="font-size:12px;letter-spacing:1px;">
          {{ readyCount }} / {{ lobbyPlayers.length }} {{ msg.playersReady }}
        </div>
      </div>
    </div>

    <!-- ── Game (felt + actions) ───────────────────────────────────── -->
    <template v-else>

    <!-- Felt Table -->
    <div class="bj-felt-wrap">
      <div class="bj-felt">

        <!-- Dealer area -->
        <div class="bj-area">
          <div class="d-flex align-items-center" style="gap:8px;">
            <span class="bj-area-label">{{ msg.dealer }}</span>
            <span class="bj-score-badge" :class="dealerScoreClass">
              {{ dealerCards.length ? (dealerScore || '—') : '—' }}
            </span>
          </div>
          <div class="bj-cards-row">
            <div
              v-for="(c, i) in dealerCards"
              :key="i"
              :class="cardClass(c, false)"
            >
              <template v-if="c && c.suit !== 'back'">
                <div class="cc-top">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
                <div class="cc-mid">{{ suitSym(c.suit) }}</div>
                <div class="cc-bot">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
              </template>
            </div>
          </div>
        </div>

        <!-- Center info -->
        <div class="bj-center-info">
          <span v-if="phase !== 'betting'" class="bj-bet-label">{{ msg.yourBet }}</span>
          <span v-if="phase !== 'betting'" class="bj-bet-amount">{{ bet ? fmt(bet) + ' Z' : '' }}</span>
          <span v-if="phase === 'finished' && result" class="bj-result-text" :class="result">{{ resultLabel }}</span>
          <span v-if="phase === 'finished' && result" class="bj-payout-flash" :class="payoutClass">{{ payoutText }}</span>
        </div>

        <!-- Player area -->
        <div class="bj-area">
          <div class="bj-cards-row">
            <div
              v-for="(c, i) in playerCards"
              :key="i"
              :class="cardClass(c, true)"
            >
              <template v-if="c && c.suit !== 'back'">
                <div class="cc-top">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
                <div class="cc-mid">{{ suitSym(c.suit) }}</div>
                <div class="cc-bot">{{ c.rank }}<br>{{ suitSym(c.suit) }}</div>
              </template>
            </div>
          </div>
          <div class="d-flex align-items-center" style="gap:8px;">
            <span class="bj-area-label">{{ msg.you }}</span>
            <span class="bj-score-badge" :class="playerScoreClass">
              {{ playerCards.length ? (playerScore || '—') : '—' }}
            </span>
          </div>
        </div>

      </div>
    </div>

    <!-- Action Panel -->
    <div class="bj-action-panel">

      <!-- Bet controls (betting / finished phases) -->
      <template v-if="phase !== 'playing'">
        <div class="bj-chip-row">
          <button
            v-for="v in chips"
            :key="v"
            class="bj-chip"
            :class="{ active: currentBet === v }"
            @click="setChip(v)"
          >{{ v >= 1000 ? (v / 1000) + 'K' : v }}</button>
        </div>
        <div class="bj-bet-input-wrap">
          <input
            type="number"
            id="bj-bet-input"
            :min="table.min_bet"
            :max="table.max_bet"
            v-model.number="currentBet"
            step="100"
          >
          <button
            class="btn btn-success font-weight-bold px-4"
            :disabled="dealing"
            @click="dealHand"
          >
            <i class="fas fa-play mr-1"></i>{{ phase === 'finished' ? msg.newHand : msg.deal }}
          </button>
        </div>
        <div class="text-muted text-center mt-1" style="font-size:11px;">
          {{ msg.betRange }}: {{ fmt(table.min_bet) }} – {{ fmt(table.max_bet) }} Z
        </div>
      </template>

      <!-- Game action buttons (playing phase) -->
      <div v-if="phase === 'playing'" class="bj-action-btns">
        <button class="btn btn-warning" :disabled="acting" @click="sendAction('hit')">
          <i class="fas fa-plus mr-1"></i>{{ msg.hit }}
        </button>
        <button class="btn btn-secondary" :disabled="acting" @click="sendAction('stand')">
          <i class="fas fa-hand-paper mr-1"></i>{{ msg.stand }}
        </button>
        <button class="btn btn-danger" :disabled="acting || !canDouble" @click="sendAction('double')">
          <i class="fas fa-times-circle mr-1"></i>{{ msg.double }}
        </button>
      </div>

    </div>

    </template><!-- /v-else game -->

  </div>
</template>

<script>
export default {
  name: 'BlackjackRoom',

  props: {
    table:     { type: Object,  required: true },
    initPlayers:{ type: Number, default: 0 },
    myUserId:  { type: Number,  required: true },
    routes:    { type: Object,  required: true },
    msg:       { type: Object,  required: true },
    csrf:      { type: String,  required: true },
  },

  data() {
    return {
      // Lobby
      inLobby:      true,
      lobbyLoading: true,
      lobbyPlayers: [],
      myIsReady:    false,
      readyLoading: false,
      countdownSec: null,

      // Game
      phase:       'betting',
      dealerCards: [],
      playerCards: [],
      dealerScore: null,
      playerScore: null,
      dealerBj:    false,
      isBlackjack: false,
      bet:         0,
      canDouble:   false,
      result:      null,
      balance:     0,
      playerCount: this.initPlayers,
      currentBet:  this.table.min_bet,
      loading:     true,
      dealing:     false,
      acting:      false,
    };
  },

  computed: {
    readyCount() {
      return this.lobbyPlayers.filter(p => p.is_ready).length;
    },

    chips() {
      const presets = [100, 500, 1000, 5000, 10000, 50000, 100000];
      return presets.filter(v => v >= this.table.min_bet && v <= this.table.max_bet);
    },

    dealerScoreClass() {
      if (this.phase !== 'finished') return '';
      if (this.dealerBj) return 'bj';
      if (this.dealerScore > 21) return 'bust';
      return '';
    },

    playerScoreClass() {
      if (this.phase !== 'finished') return '';
      if (this.isBlackjack) return 'bj';
      if (this.playerScore > 21) return 'bust';
      return '';
    },

    resultLabel() {
      if (!this.result) return '';
      return (this.msg.results && this.msg.results[this.result]) || this.result;
    },

    payoutClass() {
      if (!this.result) return '';
      if (this.result === 'push') return 'neu';
      if (['win', 'blackjack', 'dealer_bust'].includes(this.result)) return 'pos';
      return 'neg';
    },

    payoutText() {
      if (!this.result) return '';
      if (this.result === 'push') return '± 0 Z';
      if (['win', 'blackjack', 'dealer_bust'].includes(this.result)) {
        const profit = this.result === 'blackjack'
          ? '+' + Math.round(this.bet * 1.5).toLocaleString()
          : '+' + Number(this.bet).toLocaleString();
        return profit + ' Z';
      }
      return '–' + Number(this.bet).toLocaleString() + ' Z';
    },
  },

  methods: {
    fmt(n) {
      return Number(n).toLocaleString();
    },

    setChip(v) {
      this.currentBet = v;
    },

    suitSym(suit) {
      const map = { spades: '♠', hearts: '♥', diamonds: '♦', clubs: '♣' };
      return map[suit] || suit;
    },

    cardClass(c, lg) {
      if (!c || c.suit === 'back') {
        return ['play-card', 'card-back', lg ? 'lg' : ''].filter(Boolean);
      }
      const colorMap = { spades: 'black', clubs: 'black', hearts: 'red', diamonds: 'red' };
      const color = colorMap[c.suit] || 'black';
      return ['play-card', color, lg ? 'lg' : ''].filter(Boolean);
    },

    applyState(s) {
      this.phase = s.phase || 'betting';
      this.dealerCards = s.dealer_cards || [];
      this.playerCards = s.player_cards || [];
      this.dealerScore = s.dealer_score ?? null;
      this.playerScore = s.player_score ?? null;
      this.dealerBj = !!s.dealer_bj;
      this.isBlackjack = !!s.is_blackjack;
      this.bet = s.bet || 0;
      this.canDouble = !!s.can_double;
      this.result = s.result || null;
      if (s.z_coins !== undefined) {
        this.balance = s.z_coins;
      }
      // Reset currentBet to min_bet after finished hand
      if (s.phase === 'betting') {
        this.currentBet = this.table.min_bet;
      }
    },

    loadState() {
      this.loading = true;
      fetch(this.routes.state)
        .then(r => r.json())
        .then(data => {
          if (data.z_coins !== undefined) this.balance = data.z_coins;

          if (data.phase === 'lobby') {
            this.inLobby      = true;
            this.lobbyLoading = false;
            this.updateLobby(data.players || []);
            if (data.countdown_at) this.startCountdown(data.countdown_at);
            this.startLobbyPoll();
          } else if (data.state) {
            this.inLobby = false;
            this.stopLobbyPoll();
            this.applyState(data.state);
          } else {
            this.inLobby = false;
            this.stopLobbyPoll();
            this.applyState({ phase: 'betting' });
          }
        })
        .catch(() => {
          if (window.notify) window.notify('error', 'Connection error.');
        })
        .finally(() => { this.loading = false; });
    },

    dealHand() {
      const bet = parseInt(this.currentBet, 10);
      if (!bet || bet < this.table.min_bet || bet > this.table.max_bet) {
        const rawMsg = this.msg.invalidBet || '';
        const message = rawMsg
          .replace('__MIN__', this.fmt(this.table.min_bet))
          .replace('__MAX__', this.fmt(this.table.max_bet));
        if (window.notify) window.notify('warning', message);
        return;
      }

      this.dealing = true;
      this.loading = true;
      fetch(this.routes.deal, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrf,
        },
        body: JSON.stringify({ bet }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            if (window.notify) window.notify('error', data.error);
            return;
          }
          this.applyState(data.state);
        })
        .catch(() => {
          if (window.notify) window.notify('error', 'Connection error.');
        })
        .finally(() => {
          this.dealing = false;
          this.loading = false;
        });
    },

    sendAction(action) {
      this.acting = true;
      this.loading = true;
      fetch(this.routes.action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': this.csrf,
        },
        body: JSON.stringify({ action }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            if (window.notify) window.notify('error', data.error);
            return;
          }
          this.applyState(data.state);
        })
        .catch(() => {
          if (window.notify) window.notify('error', 'Connection error.');
        })
        .finally(() => {
          this.acting = false;
          this.loading = false;
        });
    },

    // ── Lobby ──────────────────────────────────────────────────────────

    updateLobby(players) {
      this.lobbyPlayers = players;
      const me = players.find(p => p.id === this.myUserId);
      this.myIsReady = me ? me.is_ready : false;
    },

    toggleReady() {
      this.readyLoading = true;
      this.loading = true;
      fetch(this.routes.ready, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
        body: JSON.stringify({}),
      })
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            if (window.notify) window.notify('error', data.error);
            return;
          }
          this.updateLobby(data.players || []);
          if (data.countdown_at) {
            this.startCountdown(data.countdown_at);
          } else {
            this.clearCountdown();
          }
        })
        .catch(() => {
          if (window.notify) window.notify('error', 'Connection error.');
        })
        .finally(() => { this.readyLoading = false; this.loading = false; });
    },

    // ── Countdown ──────────────────────────────────────────────────────

    startCountdown(countdownAt) {
      this.clearCountdown();
      const tick = () => {
        const remaining = countdownAt - Math.floor(Date.now() / 1000);
        if (remaining <= 0) {
          this.clearCountdown();
          this.stopLobbyPoll();
          this.inLobby = false;
          this.applyState({ phase: 'betting' });
          return;
        }
        this.countdownSec = remaining;
      };
      tick();
      this._countdownTimer = setInterval(tick, 500);
    },

    clearCountdown() {
      if (this._countdownTimer) {
        clearInterval(this._countdownTimer);
        this._countdownTimer = null;
      }
      this.countdownSec = null;
    },

    // ── Lobby polling (fallback when no WebSocket) ─────────────────────

    startLobbyPoll() {
      if (this._lobbyPollTimer) return;
      this._lobbyPollTimer = setInterval(() => {
        if (!this.inLobby) { this.stopLobbyPoll(); return; }
        fetch(this.routes.state)
          .then(r => r.json())
          .then(data => {
            if (data.phase === 'lobby') {
              this.updateLobby(data.players || []);
              if (data.countdown_at && !this._countdownTimer) {
                this.startCountdown(data.countdown_at);
              }
            }
          })
          .catch(() => {});
      }, 3000);
    },

    stopLobbyPoll() {
      if (this._lobbyPollTimer) {
        clearInterval(this._lobbyPollTimer);
        this._lobbyPollTimer = null;
      }
    },

    // ── WebSocket events ────────────────────────────────────────────────

    onRoomEvent(e) {
      if (e.type === 'ready_update') {
        this.updateLobby(e.players || []);
      } else if (e.type === 'countdown_start') {
        this.updateLobby(e.players || []);
        this.startCountdown(e.countdown_at);
      } else if (e.type === 'countdown_cancel') {
        this.clearCountdown();
        this.updateLobby(e.players || []);
      }
    },

    // ── Leave ──────────────────────────────────────────────────────────

    leaveTable() {
      if (window.confirmDialog) {
        window.confirmDialog(this.msg.confirmLeave, () => {
          this.$refs.leaveForm.submit();
        });
      } else {
        this.$refs.leaveForm.submit();
      }
    },
  },

  mounted() {
    this.loadState();

    if (typeof window.initEcho === 'function') window.initEcho();
    if (window.Echo) {
      window.Echo.channel(`blackjack.room.${this.table.id}`)
        .listen('BlackjackRoomUpdated', this.onRoomEvent);
    }
  },

  beforeUnmount() {
    if (window.Echo) window.Echo.leave(`blackjack.room.${this.table.id}`);
    this.clearCountdown();
    this.stopLobbyPoll();
  },
};
</script>

<style scoped>
/* ── Lobby ───────────────────────────────────────────── */
.bj-lobby {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 32px 16px;
    flex: 1;
}
.bj-lobby-inner {
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    padding: 28px 36px;
    min-width: 300px;
    max-width: 420px;
    width: 100%;
}
.bj-lobby-title {
    color: #e0e0e0;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-align: center;
    margin-bottom: 20px;
}
.bj-lobby-players {
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    overflow: hidden;
}
.bj-lobby-player {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
}
.bj-lobby-player:last-child { border-bottom: none; }
.bj-ready-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #555;
    margin-right: 10px;
    flex-shrink: 0;
    transition: background 0.3s, box-shadow 0.3s;
}
.bj-ready-dot.is-ready {
    background: #2ecc71;
    box-shadow: 0 0 6px #2ecc71aa;
}
.bj-lobby-name {
    color: #ddd;
    font-size: 13px;
}

/* ── Countdown ───────────────────────────────────────── */
.bj-countdown-overlay {
    position: absolute;
    inset: 0;
    background: rgba(8, 15, 26, 0.90);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    border-radius: 14px;
    backdrop-filter: blur(4px);
}
.bj-countdown-box {
    text-align: center;
}
.bj-countdown-label {
    font-size: 12px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.6);
    margin-bottom: 16px;
}
.bj-countdown-num {
    font-size: 96px;
    font-weight: 900;
    color: #f6c23e;
    line-height: 1;
    text-shadow: 0 0 40px rgba(246, 194, 62, 0.7);
    animation: bjCountPop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes bjCountPop {
    from { transform: scale(1.5); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

/* ── Loading overlay ─────────────────────────────────── */
.bj-loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(8, 15, 26, 0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(2px);
}
.bj-loading-box {
    background: rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(246, 194, 62, 0.3);
    border-radius: 16px;
    padding: 28px 36px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0;
}
.bj-spinner {
    width: 2.8rem;
    height: 2.8rem;
    border-width: 3px;
    color: #f6c23e;
}
</style>
