<template>
  <!-- Header -->
  <div class="row mb-2">
    <div class="col-12 d-flex align-items-center justify-content-between">
      <a :href="routes.lobby" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left"></i> {{ msg.backToLobby }}
      </a>
      <span class="text-muted small">{{ table.name }}</span>
      <form ref="leaveForm" :action="routes.leave" method="POST" class="d-inline">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="DELETE">
        <button type="button" class="btn btn-sm btn-danger" @click="confirmLeave">
          <i class="fas fa-sign-out-alt"></i> {{ msg.leaveTable }}
        </button>
      </form>
    </div>
  </div>

  <div class="tx-room">
    <!-- Main column -->
    <div class="tx-main">

      <!-- Phase / countdown -->
      <div class="tx-card">
        <div class="tx-countdown">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="font-weight-bold" :style="{ color: phaseColor }">
              <i :class="phaseIcon" class="mr-1"></i>{{ phaseLabel }}
            </span>
            <span class="text-muted small" v-if="countdown !== null">
              {{ countdown }}{{ msg.secsLeft }}
            </span>
          </div>
          <div class="tx-countdown-bar">
            <div
              class="tx-countdown-fill"
              :style="{ width: countdownPct + '%', background: phaseColor }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Dice display -->
      <div class="tx-card">
        <div class="tx-dice-canvas-wrap" :class="diceAnimClass === 'revealed' ? `glow-outcome glow-${outcome}` : ''">
          <canvas ref="diceCanvas" class="tx-dice-canvas"></canvas>
        </div>

        <!-- Outcome banner -->
        <div v-if="outcome" :class="['tx-outcome', outcome]">
          <template v-if="outcome === 'triple'">
            🎲🎲🎲 {{ msg.triple }} — {{ msg.resultTriple }}
          </template>
          <template v-else-if="outcome === 'tai'">
            {{ msg.tai }} — {{ msg.total }}: {{ sum }}
          </template>
          <template v-else>
            {{ msg.xiu }} — {{ msg.total }}: {{ sum }}
          </template>
        </div>

        <!-- My result -->
        <div v-if="myBet && myBet.result" :class="['tx-my-result', myBet.result]">
          <template v-if="myBet.result === 'win'">
            🎉 {{ msg.resultWin }} +{{ fmt(myBet.payout) }} Zoo
          </template>
          <template v-else-if="myBet.result === 'triple'">
            {{ msg.resultTriple }}
          </template>
          <template v-else>
            {{ msg.resultLose }}
          </template>
        </div>
      </div>

      <!-- Bet panel (only when betting & not yet bet) -->
      <div class="tx-card" v-if="phase === 'betting' && !myBet">
        <div class="tx-card-header">🎯 {{ msg.betting }} — {{ msg.balance }} <strong style="color:#f6c23e;">{{ fmt(balance) }} Zoo</strong></div>
        <div class="tx-bet-panel">
          <div class="tx-choice-btns">
            <button
              :class="['tx-choice-btn', 'tai', betChoice === 'tai' ? 'active' : '']"
              @click="betChoice = 'tai'"
            >
              🔴 {{ msg.tai }} (11–18)
            </button>
            <button
              :class="['tx-choice-btn', 'xiu', betChoice === 'xiu' ? 'active' : '']"
              @click="betChoice = 'xiu'"
            >
              🔵 {{ msg.xiu }} (3–10)
            </button>
          </div>
          <div class="tx-shortcuts mb-2">
            <button v-for="s in shortcuts" :key="s" class="tx-shortcut" @click="betAmount = s">
              {{ fmt(s) }}
            </button>
          </div>
          <div class="tx-amount-row">
            <input
              type="number" v-model.number="betAmount"
              :min="table.min_bet" :max="table.max_bet"
              class="form-control"
            >
            <span class="text-muted small">Zoo</span>
            <button
              class="btn btn-warning font-weight-bold"
              @click="placeBet"
              :disabled="betting || betAmount < table.min_bet || betAmount > table.max_bet"
            >
              <span v-if="betting" class="spinner-border spinner-border-sm mr-1"></span>
              {{ msg.confirmBet }}
            </button>
          </div>
          <small class="text-muted d-block mt-1">{{ fmt(table.min_bet) }}–{{ fmt(table.max_bet) }} Zoo</small>
        </div>
      </div>

      <!-- Already bet indicator -->
      <div class="tx-card" v-if="phase === 'betting' && myBet">
        <div class="tx-bet-panel text-center">
          <div class="mb-1" style="font-size:15px;font-weight:700;">
            ✅ {{ myBet.choice === 'tai' ? msg.tai : msg.xiu }} — {{ fmt(myBet.amount) }} Zoo
          </div>
          <small class="text-muted">Đang chờ kết quả...</small>
        </div>
      </div>

      <!-- Bets this round -->
      <div class="tx-card">
        <div class="tx-card-header">
          <i class="fas fa-list-ul mr-1"></i> {{ msg.betTotal }}
          <span class="float-right text-muted small">
            <span style="color:#2ecc71;">{{ msg.tai }}: {{ fmt(taiTotal) }}</span>
            &nbsp;|&nbsp;
            <span style="color:#3498db;">{{ msg.xiu }}: {{ fmt(xiuTotal) }}</span>
          </span>
        </div>
        <div style="padding:4px 0;max-height:200px;overflow-y:auto;">
          <table class="tx-bets w-100">
            <thead>
              <tr>
                <th>{{ msg.player }}</th>
                <th>{{ msg.choice }}</th>
                <th class="text-right">{{ msg.amount }}</th>
                <th v-if="phase !== 'betting'" class="text-right">{{ msg.result }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in allBets" :key="b.user_id">
                <td>{{ b.name }}</td>
                <td :class="b.choice === 'tai' ? 'tx-tai' : 'tx-xiu'">
                  {{ b.choice === 'tai' ? msg.tai : msg.xiu }}
                </td>
                <td class="text-right">{{ fmt(b.amount) }}</td>
                <td v-if="phase !== 'betting'" class="text-right">
                  <span v-if="b.result === 'win'"    class="result-win">+{{ fmt(b.payout) }}</span>
                  <span v-else-if="b.result === 'lose'"   class="result-lose">−{{ fmt(b.amount) }}</span>
                  <span v-else-if="b.result === 'triple'" class="result-triple">−{{ fmt(b.amount) }}</span>
                  <span v-else class="text-muted">—</span>
                </td>
              </tr>
              <tr v-if="allBets.length === 0">
                <td colspan="4" class="text-center text-muted py-2" style="font-size:11px;">Chưa có cược nào</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /tx-main -->

    <!-- Side column -->
    <div class="tx-side">

      <!-- Balance -->
      <div class="tx-card" style="padding:12px 14px;">
        <div style="font-size:11px;color:#aaa;margin-bottom:2px;">{{ msg.balance }}</div>
        <div style="font-size:22px;font-weight:800;color:#f6c23e;">{{ fmt(balance) }}</div>
        <div style="font-size:10px;color:#666;">Zoo Coins</div>
      </div>

      <!-- Log -->
      <div class="tx-card" style="flex:1;padding:10px 12px;overflow-y:auto;">
        <div style="font-size:12px;font-weight:700;color:#fff;margin-bottom:6px;">
          <i class="fas fa-scroll mr-1"></i> {{ msg.log }}
        </div>
        <div v-for="(entry, i) in log" :key="i" class="tx-log-entry">{{ entry }}</div>
      </div>

      <!-- Chat -->
      <div class="tx-card tx-chat-panel">
        <div class="tx-card-header"><i class="fas fa-comments mr-1"></i> {{ msg.chatPanel }}</div>
        <div class="tx-chat-messages" ref="chatBox">
          <div v-for="m in chatMessages" :key="m.id"
               :class="['tx-chat-msg', m.user_id === myUserId ? 'tx-chat-mine' : '']">
            <div class="tx-chat-meta">
              <span class="tx-chat-name">{{ m.user_id === myUserId ? msg.chatMe : m.name }}</span>
              <span class="tx-chat-time">{{ m.time }}</span>
            </div>
            <div class="tx-chat-bubble">{{ m.message }}</div>
          </div>
          <div v-if="chatMessages.length === 0" class="tx-chat-empty">{{ msg.chatEmpty }}</div>
        </div>
        <div class="tx-chat-input-row">
          <input
            v-model="chatInput"
            class="tx-chat-input"
            :placeholder="msg.chatPlaceholder"
            maxlength="500"
            @keydown.enter.prevent="sendChatMessage"
            :disabled="chatSending"
          >
          <button class="tx-chat-send" @click="sendChatMessage" :disabled="chatSending || !chatInput.trim()">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
      </div>

    </div><!-- /tx-side -->
  </div><!-- /tx-room -->

  <!-- Loading overlay -->
  <div v-if="loading" class="tx-loading-overlay">
    <div class="spinner-border text-light" style="width:3rem;height:3rem;"></div>
  </div>

  <!-- Hidden leave form -->
  <form ref="leaveFormHidden" :action="routes.leave" method="POST" style="display:none;">
    <input type="hidden" name="_token" :value="csrf">
    <input type="hidden" name="_method" value="DELETE">
  </form>
</template>

<script>
import { DiceRenderer } from '../utils/DiceRenderer.js';

export default {
    name: 'TaixiuRoom',

    props: {
        table:         { type: Object, required: true },
        myUserId:      { type: Number, required: true },
        routes:        { type: Object, required: true },
        msg:           { type: Object, required: true },
        csrf:          { type: String, required: true },
        initialZCoins: { type: Number, default: 0 },
    },

    data() {
        return {
            loading:  true,
            phase:    'betting',
            gameId:   null,
            dice:     [],
            sum:      null,
            outcome:  null,
            allBets:  [],
            myBet:    null,
            balance:  this.initialZCoins,
            deadline: null,
            countdown:     null,
            countdownPct:  100,
            betChoice: 'tai',
            betAmount: this.table.min_bet || 100,
            betting:   false,
            log:       [],
            chatMessages: [],
            chatInput:    '',
            chatSending:  false,
            _countdownTimer: null,
            diceAnimClass:  'static',   // static | pre-rolling | landing | revealed
            diceRevealKey:  0,
        };
    },

    computed: {
        taiTotal() { return this.allBets.filter(b => b.choice === 'tai').reduce((s, b) => s + b.amount, 0); },
        xiuTotal() { return this.allBets.filter(b => b.choice === 'xiu').reduce((s, b) => s + b.amount, 0); },

        shortcuts() {
            const min = this.table.min_bet || 100;
            return [min, min*2, min*5, min*10, min*20].filter(v => v <= this.table.max_bet);
        },

        phaseLabel() {
            if (this.phase === 'betting') return this.msg.betting;
            if (this.phase === 'result')  return this.outcome ? this.outcomeLabel : '...';
            return this.msg.waiting;
        },
        outcomeLabel() {
            if (!this.outcome) return '';
            if (this.outcome === 'triple') return this.msg.triple;
            return this.outcome === 'tai' ? this.msg.tai : this.msg.xiu;
        },
        phaseColor() {
            if (this.phase === 'result') {
                if (this.outcome === 'tai')    return '#2ecc71';
                if (this.outcome === 'xiu')    return '#3498db';
                if (this.outcome === 'triple') return '#e74c3c';
            }
            return '#f6c23e';
        },
        phaseIcon() {
            if (this.phase === 'betting') return 'fas fa-coins';
            if (this.phase === 'result')  return 'fas fa-dice';
            return 'fas fa-hourglass-half';
        },
    },

    watch: {
        // Start spinning when ≤ 4 seconds remain in betting phase
        countdown(val) {
            if (val !== null && val <= 4 && val > 0 &&
                this.phase === 'betting' && this.diceAnimClass === 'static') {
                this.diceAnimClass = 'pre-rolling';
                this._dr?.startRolling();
            }
        },
    },

    mounted() {
        this._intentionalLeave = false;
        window.addEventListener('beforeunload', this.onBeforeUnload);

        this.$nextTick(() => { this._initDiceRenderer(); });

        this.loadState();
        this.loadChatMessages();

        if (typeof window.initEcho === 'function') window.initEcho();
        if (window.Echo) {
            window.Echo.channel(`taixiu.room.${this.table.id}`)
                .listen('TaixiuRoomUpdated', this.onRoomEvent);
        }
    },

    beforeUnmount() {
        window.removeEventListener('beforeunload', this.onBeforeUnload);
        if (window.Echo) window.Echo.leave(`taixiu.room.${this.table.id}`);
        if (this._countdownTimer) clearInterval(this._countdownTimer);
        this._dr?.destroy();
    },

    methods: {
        fmt(n) {
            if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
            if (n >= 1000)    return (n / 1000).toFixed(1) + 'k';
            return Number(n).toLocaleString();
        },

        // ── Three.js dice renderer ────────────────────────────────────────
        _initDiceRenderer() {
            const canvas = this.$refs.diceCanvas;
            if (!canvas) return;
            const wrap = canvas.parentElement;
            const w = wrap.clientWidth || 480;
            const h = 190;
            canvas.width  = w;
            canvas.height = h;
            this._dr = new DiceRenderer(canvas, w, h);
        },

        // ── Game state ─────────────────────────────────────────────────────
        applyState(s) {
            if (!s) return;
            const prevPhase = this.phase;
            this.phase   = s.phase;
            this.gameId  = s.game_id;
            this.dice    = s.dice   || [];
            this.sum     = s.sum;
            this.outcome = s.outcome;
            this.allBets = s.bets   || [];
            this.myBet   = s.my_bet || null;
            this.log     = s.log    || [];
            if (s.z_coins !== undefined) {
                this.balance = s.z_coins;
                const el = document.getElementById('nav-zcoin-balance');
                if (el) el.textContent = s.z_coins.toLocaleString();
            }
            if (s.deadline) this.startCountdown(s.deadline);

            // Dice animation state machine
            if (s.phase === 'result' && this.dice.length === 3) {
                if (prevPhase !== 'result') {
                    this.diceRevealKey++;
                    this.diceAnimClass = 'landing';
                    // stagger 180ms × 2 + 1100ms animation = ~1460ms total
                    this._dr?.land(this.dice, () => { this.diceAnimClass = 'revealed'; });
                } else {
                    this.diceAnimClass = 'revealed';
                }
            } else {
                // New betting round – reset (watch triggers pre-rolling at ≤4s)
                this.diceAnimClass = 'static';
                this._dr?.setStatic();
            }
        },

        loadState() {
            this.loading = true;
            fetch(this.routes.state, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(res => {
                    if (res.z_coins !== undefined) this.balance = res.z_coins;
                    if (res.game) this.applyState(res.game);
                })
                .finally(() => { this.loading = false; });
        },

        // ── Countdown ──────────────────────────────────────────────────────
        startCountdown(deadline) {
            this.deadline = deadline;
            if (this._countdownTimer) clearInterval(this._countdownTimer);

            const duration = this.phase === 'betting'
                ? 30  // BETTING_DURATION
                : 8;  // RESULT_DURATION

            const tick = () => {
                const left = Math.max(0, deadline - Math.floor(Date.now() / 1000));
                this.countdown    = left;
                this.countdownPct = (left / duration) * 100;
                if (left <= 0) { clearInterval(this._countdownTimer); this._countdownTimer = null; }
            };
            tick();
            this._countdownTimer = setInterval(tick, 500);
        },

        // ── Betting ────────────────────────────────────────────────────────
        placeBet() {
            if (this.betting) return;
            this.betting = true;
            fetch(this.routes.bet, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ choice: this.betChoice, amount: this.betAmount }),
            })
                .then(r => r.json())
                .then(res => {
                    if (res.error) { window.notify && window.notify('error', res.error); }
                    else if (res.state) { this.applyState(res.state); }
                })
                .catch(() => window.notify && window.notify('error', 'Lỗi kết nối.'))
                .finally(() => { this.betting = false; });
        },

        // ── WebSocket ──────────────────────────────────────────────────────
        onRoomEvent(e) {
            if (['round_started', 'bet_placed', 'dice_rolled', 'round_finished'].includes(e.type)) {
                if (e.state) {
                    // Merge viewer's own bet into the state (WS state is not per-viewer)
                    const myBet = this.allBets.find(b => b.user_id === this.myUserId) || null;
                    this.applyState({ ...e.state, my_bet: e.state.my_bet ?? myBet });
                }
            } else if (e.type === 'chat_message' && e.chat_message) {
                this.pushChatMessage(e.chat_message);
            }
        },

        // ── Chat ───────────────────────────────────────────────────────────
        loadChatMessages() {
            fetch(this.routes.chatMessages)
                .then(r => r.json())
                .then(res => {
                    this.chatMessages = res.messages || [];
                    this.$nextTick(() => this.scrollChatToBottom());
                });
        },

        sendChatMessage() {
            const txt = this.chatInput.trim();
            if (!txt || this.chatSending) return;
            this.chatSending = true;
            fetch(this.routes.chatSend, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify({ message: txt }),
            })
                .then(r => r.json())
                .then(res => {
                    if (res.message) { this.pushChatMessage(res.message); this.chatInput = ''; }
                })
                .finally(() => { this.chatSending = false; });
        },

        pushChatMessage(m) {
            this.chatMessages.push(m);
            if (this.chatMessages.length > 200) this.chatMessages.shift();
            this.$nextTick(() => this.scrollChatToBottom());
        },

        scrollChatToBottom() {
            const el = this.$refs.chatBox;
            if (el) el.scrollTop = el.scrollHeight;
        },

        // ── Leave ──────────────────────────────────────────────────────────
        confirmLeave() {
            this._intentionalLeave = true;
            if (window.confirmDialog) {
                window.confirmDialog(this.msg.confirmLeave, () => this.$refs.leaveForm.submit());
            } else {
                this.$refs.leaveForm.submit();
            }
        },

        onBeforeUnload() {
            if (window.Echo) window.Echo.leave(`taixiu.room.${this.table.id}`);
            if (this._countdownTimer) clearInterval(this._countdownTimer);
            if (!this._intentionalLeave) {
                fetch(this.routes.leave, {
                    method: 'POST',
                    keepalive: true,
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': this.csrf },
                    body: '_method=DELETE',
                });
            }
        },
    },
};
</script>
