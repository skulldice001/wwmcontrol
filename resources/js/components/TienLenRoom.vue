<template>
  <!-- Header -->
  <div class="row mb-2">
    <div class="col-12 d-flex align-items-center justify-content-between">
      <a :href="routes.lobby" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left"></i> Sảnh
      </a>
      <span class="text-muted small">{{ tableData.name }}
        <span class="badge badge-info ml-1" v-if="tableData.is_ai_mode">vs AI</span>
        <span class="badge badge-secondary ml-1">{{ variantLabel }}</span>
      </span>
      <form :action="routes.leave" method="POST" class="d-inline">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" class="btn btn-sm btn-danger">
          <i class="fas fa-sign-out-alt"></i> Rời bàn
        </button>
      </form>
    </div>
  </div>

  <div class="tl-room">
    <!-- Left: game board -->
    <div class="tl-main">

      <!-- Waiting room -->
      <div v-if="tableData.status === 'waiting'" class="tl-card">
        <div class="tl-card-header">
          <i class="fas fa-users mr-1"></i> Phòng chờ · Phí: <strong style="color:#f6c23e">{{ tableData.entry_fee }} Zoo</strong> / người
        </div>
        <div class="tl-seats">
          <div
            v-for="seat in 4"
            :key="seat"
            class="tl-seat"
            :class="seatPlayer(seat) ? 'tl-seat-occupied' : 'tl-seat-empty'"
          >
            <div v-if="seatPlayer(seat)">
              <i class="fas fa-user mr-1"></i>
              <strong>{{ seatPlayer(seat).name }}</strong>
              <span class="ml-1">
                <span v-if="seatPlayer(seat).is_ready" class="badge badge-success">Sẵn sàng</span>
                <span v-else class="badge badge-secondary">Chờ...</span>
              </span>
            </div>
            <div v-else class="text-muted">
              <i class="fas fa-chair mr-1"></i> Ghế {{ seat }} — Đang chờ
            </div>
          </div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button
            class="btn btn-sm mr-2"
            :class="amReady ? 'btn-warning' : 'btn-success'"
            @click="toggleReady"
            :disabled="readyLoading"
            v-if="!tableData.is_ai_mode"
          >
            {{ amReady ? 'Hủy sẵn sàng' : 'Sẵn sàng' }}
          </button>
          <button
            class="btn btn-sm btn-primary"
            @click="startGame"
            :disabled="startLoading"
            v-if="isOwner || tableData.is_ai_mode"
          >
            <span v-if="startLoading"><i class="fas fa-spinner fa-spin mr-1"></i></span>
            Bắt đầu chơi
          </button>
        </div>
      </div>

      <!-- Playing board -->
      <div v-if="tableData.status === 'playing' && game" class="tl-card">

        <!-- Opponents (top) -->
        <div class="tl-opponents">
          <div
            v-for="(player, idx) in opponentPlayers"
            :key="idx"
            class="tl-opponent"
            :class="{ 'tl-current-turn': game.current_player === player.idx }"
          >
            <div class="tl-opponent-name">
              <span v-if="player.is_ai" class="badge badge-warning mr-1">AI</span>
              {{ player.name }}
              <span v-if="player.finished" class="badge badge-info ml-1">Hạng {{ player.rank }}</span>
            </div>
            <div class="tl-opponent-cards">
              <div
                v-for="i in getHandCount(player)"
                :key="i"
                class="tl-card-back"
              ></div>
              <span class="tl-card-count">{{ getHandCount(player) }}</span>
            </div>
            <div v-if="game.current_player === player.idx" class="tl-turn-indicator">
              <i class="fas fa-arrow-down text-warning"></i>
            </div>
          </div>
        </div>

        <!-- Center board: last played combo -->
        <div class="tl-board">
          <div v-if="game.last_combo" class="tl-last-played">
            <div class="text-muted small mb-1">Bài vừa đánh:</div>
            <div class="tl-cards-row">
              <div
                v-for="card in game.last_combo.cards"
                :key="card"
                class="tl-card-face"
                :class="cardColor(card)"
              >
                <span class="tl-card-rank">{{ cardRank(card) }}</span>
                <span class="tl-card-suit">{{ cardSuit(card) }}</span>
              </div>
            </div>
          </div>
          <div v-else class="text-muted text-center">
            <i class="fas fa-table mr-1"></i> Bàn trống — đánh bất kỳ bộ bài
          </div>
        </div>

        <!-- My turn indicator -->
        <div v-if="isMyTurn" class="tl-my-turn-notice">
          <i class="fas fa-hand-point-right mr-1"></i> Đến lượt bạn!
        </div>

        <!-- My hand -->
        <div class="tl-my-hand">
          <div class="tl-hand-label">
            Bài của bạn ({{ myHand.length }} lá):
            <span v-if="game.first_turn" class="badge badge-danger ml-1">Lượt đầu: đánh bài có 3♠</span>
          </div>
          <div class="tl-cards-row tl-selectable">
            <div
              v-for="card in myHand"
              :key="card"
              class="tl-card-face"
              :class="[cardColor(card), { 'tl-selected': selectedCards.includes(card) }]"
              @click="isMyTurn && !myPlayer.finished ? toggleCard(card) : null"
            >
              <span class="tl-card-rank">{{ cardRank(card) }}</span>
              <span class="tl-card-suit">{{ cardSuit(card) }}</span>
            </div>
          </div>
        </div>

        <!-- Action buttons -->
        <div v-if="isMyTurn && !myPlayer.finished" class="tl-actions mt-2">
          <button
            class="btn btn-success mr-2"
            :disabled="selectedCards.length === 0 || playLoading"
            @click="playSelected"
          >
            <i class="fas fa-play mr-1"></i>
            Đánh {{ selectedCards.length > 0 ? '(' + selectedCards.length + ' lá)' : '' }}
          </button>
          <button
            class="btn btn-secondary mr-2"
            @click="passCard"
            :disabled="game.last_combo === null || playLoading"
          >
            Bỏ lượt
          </button>
          <button class="btn btn-outline-secondary btn-sm" @click="selectedCards = []">
            Bỏ chọn
          </button>
        </div>

        <div v-if="myPlayer && myPlayer.finished" class="tl-finished-notice">
          <i class="fas fa-check-circle mr-1 text-success"></i>
          Bạn đã đánh hết bài! Hạng <strong>{{ myPlayer.rank }}</strong>
        </div>
      </div>

      <!-- Game finished -->
      <div v-if="tableData.status === 'finished'" class="tl-card">
        <div class="tl-card-header text-center">
          <i class="fas fa-trophy mr-1 text-warning"></i> Kết quả ván
        </div>
        <div v-if="game" class="tl-results">
          <div
            v-for="(player, idx) in sortedPlayers"
            :key="idx"
            class="tl-result-row"
            :class="player.rank === 1 ? 'tl-winner-row' : 'tl-loser-row'"
          >
            <span class="tl-rank">{{ rankLabel(player.rank) }}</span>
            <span class="tl-player-name">
              <span v-if="player.is_ai" class="badge badge-warning mr-1">AI</span>
              {{ player.name }}
            </span>
          </div>
        </div>
        <div class="mt-3 text-center">
          <a :href="routes.lobby" class="btn btn-primary">Về sảnh</a>
        </div>
      </div>

      <!-- Error -->
      <div v-if="errorMsg" class="alert alert-danger mt-2 py-1 px-2">
        {{ errorMsg }}
        <button type="button" class="close ml-2" @click="errorMsg = ''"><span>&times;</span></button>
      </div>

      <!-- Game log -->
      <div v-if="game && game.log && game.log.length" class="tl-card mt-2">
        <div class="tl-card-header">Lịch sử ván</div>
        <div class="tl-log">
          <div v-for="(entry, i) in game.log.slice().reverse().slice(0, 20)" :key="i" class="tl-log-entry">
            {{ entry }}
          </div>
        </div>
      </div>
    </div>

    <!-- Right: chat + players -->
    <div class="tl-sidebar">
      <!-- Players list -->
      <div class="tl-card mb-2">
        <div class="tl-card-header"><i class="fas fa-users mr-1"></i> Người chơi</div>
        <div v-for="player in allPlayers" :key="player.user_id || player.name" class="tl-player-row">
          <span v-if="player.is_ai" class="badge badge-warning mr-1">AI</span>
          <span>{{ player.name }}</span>
          <span v-if="player.finished" class="badge badge-info ml-1 float-right">Hạng {{ player.rank }}</span>
          <span v-else-if="game && game.current_player !== undefined && allPlayers.indexOf(player) === game.current_player"
                class="badge badge-warning ml-1 float-right">Đang đánh</span>
        </div>
      </div>

      <!-- Chat -->
      <div class="tl-card tl-chat-card">
        <div class="tl-card-header"><i class="fas fa-comment mr-1"></i> Chat</div>
        <div class="tl-chat-messages" ref="chatBox">
          <div v-for="m in chatMessages" :key="m.id" class="tl-chat-msg">
            <strong>{{ m.user_name }}:</strong> {{ m.body }}
          </div>
        </div>
        <form @submit.prevent="sendChat" class="tl-chat-form">
          <input
            v-model="chatInput"
            type="text"
            class="form-control form-control-sm"
            placeholder="Nhắn tin..."
            maxlength="200"
          >
          <button type="submit" class="btn btn-sm btn-primary ml-1">Gửi</button>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
const RANKS = ['3','4','5','6','7','8','9','10','J','Q','K','A','2'];
const SUITS = ['♠','♣','♦','♥'];
const SUIT_COLORS = ['black','black','red','red'];

export default {
  name: 'TienLenRoom',

  props: {
    initTable:    { type: Object,  required: true },
    initGame:     { type: Object,  default: null  },
    initMyHand:   { type: Array,   default: () => [] },
    initMessages: { type: Array,   default: () => [] },
    initAmReady:  { type: Boolean, default: false },
    routes:       { type: Object,  required: true },
    csrf:         { type: String,  required: true },
    userId:       { type: Number,  required: true },
  },

  data() {
    return {
      tableData:     this.initTable,
      game:          this.initGame ? this.stripOpponentHands(this.initGame) : null,
      myHand:        [...this.initMyHand],
      chatMessages:  [...this.initMessages],
      amReady:       this.initAmReady,
      selectedCards: [],
      chatInput:     '',
      playLoading:   false,
      readyLoading:  false,
      startLoading:  false,
      errorMsg:      '',
    };
  },

  computed: {
    variantLabel() {
      return this.tableData.variant === 'mien_nam' ? 'Miền Nam' : 'Miền Bắc';
    },

    isOwner() {
      return this.tableData.owner_id === this.userId;
    },

    isMyTurn() {
      if (!this.game || this.game.phase === 'finished') return false;
      const cur = this.game.current_player;
      return this.game.players[cur]?.user_id === this.userId;
    },

    myPlayer() {
      if (!this.game) return null;
      return this.game.players.find(p => p.user_id === this.userId) ?? null;
    },

    allPlayers() {
      if (this.game) return this.game.players;
      return this.tableData.players || [];
    },

    opponentPlayers() {
      if (!this.game) return [];
      return this.game.players
        .map((p, idx) => ({ ...p, idx }))
        .filter(p => p.user_id !== this.userId);
    },

    sortedPlayers() {
      if (!this.game) return [];
      return [...this.game.players].sort((a, b) => (a.rank ?? 99) - (b.rank ?? 99));
    },
  },

  methods: {
    cardRank(card) {
      return RANKS[Math.floor(card / 4)];
    },
    cardSuit(card) {
      return SUITS[card % 4];
    },
    cardColor(card) {
      return SUIT_COLORS[card % 4] === 'red' ? 'tl-card-red' : 'tl-card-black';
    },

    getHandCount(player) {
      // In game state, opponents' hand is replaced by count (number)
      const val = player.hand;
      if (typeof val === 'number') return val;
      if (Array.isArray(val)) return val.length;
      return 0;
    },

    seatPlayer(seat) {
      const players = this.tableData.players || [];
      return players.find(p => p.seat === seat || p.pivot?.seat === seat) ?? null;
    },

    toggleCard(card) {
      const idx = this.selectedCards.indexOf(card);
      if (idx >= 0) {
        this.selectedCards.splice(idx, 1);
      } else {
        this.selectedCards.push(card);
      }
    },

    rankLabel(rank) {
      const labels = { 1: '🥇 1st', 2: '🥈 2nd', 3: '🥉 3rd', 4: '4th' };
      return labels[rank] || rank;
    },

    async toggleReady() {
      this.readyLoading = true;
      try {
        const res = await fetch(this.routes.ready, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.ok) this.amReady = data.is_ready;
      } finally {
        this.readyLoading = false;
      }
    },

    async startGame() {
      this.startLoading = true;
      this.errorMsg = '';
      try {
        const res = await fetch(this.routes.start, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.error) { this.errorMsg = data.error; return; }
        if (data.ok) await this.fetchState();
      } finally {
        this.startLoading = false;
      }
    },

    async playSelected() {
      if (this.playLoading) return;
      this.playLoading = true;
      this.errorMsg = '';
      try {
        const res = await fetch(this.routes.play, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrf,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ cards: this.selectedCards }),
        });
        const data = await res.json();
        if (data.error) { this.errorMsg = data.error; return; }
        this.selectedCards = [];
        await this.fetchState();
      } finally {
        this.playLoading = false;
      }
    },

    async passCard() {
      if (this.playLoading) return;
      this.playLoading = true;
      this.errorMsg = '';
      try {
        const res = await fetch(this.routes.play, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': this.csrf,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ cards: [] }),
        });
        const data = await res.json();
        if (data.error) { this.errorMsg = data.error; return; }
        await this.fetchState();
      } finally {
        this.playLoading = false;
      }
    },

    async sendChat() {
      const body = this.chatInput.trim();
      if (!body) return;
      this.chatInput = '';
      const res = await fetch(this.routes.chat, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': this.csrf,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ body }),
      });
      const data = await res.json();
      if (data.ok) {
        this.chatMessages.push(data.message);
        this.$nextTick(() => {
          const box = this.$refs.chatBox;
          if (box) box.scrollTop = box.scrollHeight;
        });
      }
    },

    async fetchState() {
      const res  = await fetch(this.routes.state, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();

      this.tableData = {
        ...this.tableData,
        status:  data.table_status,
        players: data.players,
      };
      this.amReady = data.is_ready;
      this.myHand  = data.my_hand || [];

      if (data.game) {
        this.game = this.stripOpponentHands(data.game);
      }
    },

    stripOpponentHands(game) {
      if (!game) return null;
      const g = { ...game, players: game.players.map(p => ({ ...p })) };
      g.players.forEach(p => {
        if (p.user_id !== this.userId && Array.isArray(p.hand)) {
          p.hand = p.hand.length;
        }
      });
      return g;
    },

    setupEcho() {
      if (typeof window.initEcho === 'function') window.initEcho();
      if (!window.Echo) return;

      window.Echo.channel('tienlen.' + this.tableData.id)
        .listen('.table.updated', () => {
          this.fetchState();
        });
    },
  },

  mounted() {
    this.setupEcho();
  },
};
</script>

<style scoped>
.tl-room {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.tl-main {
  flex: 1;
  min-width: 0;
}

.tl-sidebar {
  width: 240px;
  flex-shrink: 0;
}

.tl-card {
  background: #1e2a3a;
  border: 1px solid #2d3f55;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 8px;
}

.tl-card-header {
  font-weight: 600;
  font-size: 0.85rem;
  color: #aab;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

/* Seats */
.tl-seats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}

.tl-seat {
  padding: 10px;
  border-radius: 6px;
  min-height: 52px;
  display: flex;
  align-items: center;
}

.tl-seat-occupied { background: #243040; border: 1px solid #3a5070; }
.tl-seat-empty    { background: #161f2b; border: 1px dashed #2a3a4a; color: #556; }

/* Opponents row */
.tl-opponents {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-bottom: 8px;
}

.tl-opponent {
  background: #1a2535;
  border: 1px solid #2a3a4a;
  border-radius: 6px;
  padding: 6px 10px;
  min-width: 80px;
  text-align: center;
  position: relative;
}

.tl-current-turn {
  border-color: #f6c23e !important;
  box-shadow: 0 0 8px rgba(246, 194, 62, 0.4);
}

.tl-opponent-name {
  font-size: 0.78rem;
  font-weight: 600;
  margin-bottom: 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tl-opponent-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 2px;
  justify-content: center;
  align-items: center;
}

.tl-card-back {
  width: 16px;
  height: 22px;
  background: linear-gradient(135deg, #1a4a8a, #0d2a5a);
  border: 1px solid #2a5a9a;
  border-radius: 2px;
}

.tl-card-count {
  font-size: 0.7rem;
  color: #aab;
  margin-left: 2px;
}

.tl-turn-indicator {
  position: absolute;
  bottom: -14px;
  left: 50%;
  transform: translateX(-50%);
}

/* Board */
.tl-board {
  background: #12201a;
  border: 1px solid #1e3a2a;
  border-radius: 6px;
  padding: 10px;
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 8px 0;
}

.tl-last-played { text-align: center; }

/* Card faces */
.tl-cards-row {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  align-items: flex-end;
}

.tl-card-face {
  width: 38px;
  height: 54px;
  background: #fff;
  border: 1px solid #ccc;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: default;
  user-select: none;
  transition: transform 0.1s, box-shadow 0.1s;
  position: relative;
}

.tl-selectable .tl-card-face {
  cursor: pointer;
}

.tl-selectable .tl-card-face:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.4);
}

.tl-card-face.tl-selected {
  transform: translateY(-8px);
  box-shadow: 0 6px 12px rgba(246, 194, 62, 0.6);
  border-color: #f6c23e;
}

.tl-card-rank {
  font-size: 0.9rem;
  font-weight: 700;
  line-height: 1;
}

.tl-card-suit {
  font-size: 1rem;
  line-height: 1;
}

.tl-card-black { color: #1a1a1a; }
.tl-card-red   { color: #c00; }

/* My hand */
.tl-my-hand {
  margin-top: 12px;
  padding: 8px;
  background: #0f1a25;
  border-radius: 6px;
}

.tl-hand-label {
  font-size: 0.78rem;
  color: #aab;
  margin-bottom: 6px;
}

/* Turn notice */
.tl-my-turn-notice {
  text-align: center;
  color: #f6c23e;
  font-weight: 600;
  padding: 4px 0;
  font-size: 0.9rem;
}

.tl-finished-notice {
  text-align: center;
  padding: 6px;
  color: #4caf50;
  font-size: 0.88rem;
}

/* Actions */
.tl-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }

/* Results */
.tl-results { padding: 4px 0; }

.tl-result-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 8px;
  border-radius: 4px;
  margin-bottom: 4px;
}

.tl-winner-row { background: rgba(246, 194, 62, 0.15); border: 1px solid rgba(246, 194, 62, 0.3); }
.tl-loser-row  { background: #1a2535; border: 1px solid #2a3a4a; }

.tl-rank { font-size: 1.1rem; min-width: 48px; }
.tl-player-name { font-weight: 600; }

/* Log */
.tl-log {
  max-height: 120px;
  overflow-y: auto;
  font-size: 0.78rem;
  color: #aab;
}

.tl-log-entry { padding: 1px 0; border-bottom: 1px solid #1a2535; }

/* Player list */
.tl-player-row {
  padding: 4px 0;
  border-bottom: 1px solid #1e2a3a;
  font-size: 0.82rem;
}

/* Chat */
.tl-chat-card { display: flex; flex-direction: column; }

.tl-chat-messages {
  flex: 1;
  max-height: 180px;
  overflow-y: auto;
  font-size: 0.78rem;
  padding: 4px 0;
  margin-bottom: 6px;
}

.tl-chat-msg { padding: 2px 0; border-bottom: 1px solid #1e2a3a; word-break: break-word; }

.tl-chat-form {
  display: flex;
  gap: 4px;
}

/* Responsive */
@media (max-width: 768px) {
  .tl-room    { flex-direction: column; }
  .tl-sidebar { width: 100%; }
  .tl-card-face { width: 30px; height: 44px; }
  .tl-card-rank { font-size: 0.75rem; }
  .tl-card-suit { font-size: 0.85rem; }
}
</style>
