<template>
  <div class="bingo-wrap">

    <!-- Header -->
    <div class="bingo-header">
      <a :href="routes.back" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Sảnh
      </a>
      <div class="bingo-title">
        <i class="fas fa-th mr-2" style="color:#1abc9c;"></i>{{ tableName }}
      </div>
      <div class="bingo-pot" v-if="pot > 0">
        <i class="fas fa-coins mr-1" style="color:#f6c23e;"></i>
        <span style="color:#f6c23e;font-weight:800;">{{ pot.toLocaleString() }}</span>
        <small class="text-muted ml-1">Zoo</small>
      </div>
      <div v-else class="text-muted" style="font-size:13px;">Phí: {{ entryFee.toLocaleString() }} Zoo</div>
    </div>

    <!-- WINNER overlay -->
    <div v-if="showWinner" class="bingo-winner-overlay" @click="showWinner = false">
      <div class="bingo-winner-box">
        <div class="bingo-winner-title">🎉 BINGO! 🎉</div>
        <div v-if="isWinner" class="bingo-winner-me">Chúc mừng bạn đã thắng!</div>
        <div v-else class="bingo-winner-other">
          {{ winnerNames.join(', ') }} đã BINGO!
        </div>
        <div class="bingo-winner-pot">+{{ Math.floor(pot / Math.max(winnerIds.length,1)).toLocaleString() }} Zoo</div>
        <small class="text-muted">(Nhấn để đóng)</small>
      </div>
    </div>

    <div class="bingo-layout">

      <!-- Left: card + called numbers -->
      <div class="bingo-main">

        <!-- Lobby phase -->
        <div v-if="phase === 'lobby'" class="bingo-lobby">
          <div class="bingo-lobby-title">Phòng chờ</div>
          <div class="bingo-lobby-sub">Cần ít nhất <strong>{{ minPlayers }}</strong> người sẵn sàng để bắt đầu</div>

          <div class="bingo-player-list mb-3">
            <div v-for="p in players" :key="p.id" class="bingo-player-row">
              <span class="bingo-player-name" :class="{me: p.id === userId}">{{ p.name }}</span>
              <span :class="['badge', p.is_ready ? 'badge-success' : 'badge-secondary']">
                {{ p.is_ready ? 'Sẵn sàng' : 'Chờ' }}
              </span>
            </div>
            <div v-if="!players.length" class="text-muted text-center py-3">Đang tải...</div>
          </div>

          <div class="bingo-ready-status mb-3">
            <span style="font-size:13px;color:rgba(255,255,255,.5);">
              {{ readyCount }}/{{ players.length }} sẵn sàng
              <span v-if="players.length < minPlayers" style="color:#e74c3c;">
                — cần thêm {{ minPlayers - players.length }} người
              </span>
            </span>
          </div>

          <button
            :class="['btn', 'w-100', isReady ? 'btn-outline-warning' : 'btn-warning']"
            :disabled="toggling"
            @click="toggleReady"
          >
            <span v-if="toggling"><i class="fas fa-spinner fa-spin mr-1"></i></span>
            {{ isReady ? 'Hủy sẵn sàng' : 'Sẵn sàng' }}
          </button>

          <button class="btn btn-outline-secondary btn-sm w-100 mt-2" @click="leaveTable">
            <i class="fas fa-sign-out-alt mr-1"></i> Rời bàn
          </button>
        </div>

        <!-- Playing / Finished phase -->
        <template v-else>
          <!-- Last called number -->
          <div class="bingo-last-called">
            <div class="bingo-ball-big" :class="{'pulse': justCalled}">
              {{ lastCalled ? pad(lastCalled) : '—' }}
            </div>
            <div class="bingo-called-row">
              <span v-for="n in recentCalled" :key="n" class="bingo-ball-sm">{{ pad(n) }}</span>
              <span v-if="!recentCalled.length" class="text-muted" style="font-size:12px;">Chờ số đầu tiên...</span>
            </div>
            <div style="font-size:11px;color:rgba(255,255,255,.35);margin-top:4px;">
              {{ calledNumbers.length }}/75 số đã gọi
            </div>
          </div>

          <!-- Bingo card -->
          <div v-if="myCard" class="bingo-card-wrap">
            <div class="bingo-card-header">
              <span v-for="col in ['B','I','N','G','O']" :key="col" class="bingo-col-label">{{ col }}</span>
            </div>
            <div class="bingo-card-grid">
              <template v-for="(row, r) in myCard" :key="r">
                <div
                  v-for="(num, c) in row" :key="c"
                  :class="['bingo-cell',
                    isCellMarked(r,c) ? 'marked' : '',
                    isFree(r,c) ? 'free' : '',
                    justCalledNum === num && num !== 0 ? 'just-called' : ''
                  ]"
                >
                  <span v-if="isFree(r,c)" class="bingo-free">FREE</span>
                  <span v-else>{{ pad(num) }}</span>
                </div>
              </template>
            </div>
          </div>
          <div v-else class="text-center text-muted py-4">Đang tải thẻ...</div>

          <div class="mt-3 text-center" style="font-size:12px;color:rgba(255,255,255,.4);">
            Đã đánh dấu: <strong style="color:#1abc9c;">{{ markCount }}</strong>/25 ô
          </div>

          <button class="btn btn-outline-secondary btn-sm mt-3 w-100" @click="leaveTable">
            <i class="fas fa-sign-out-alt mr-1"></i> Rời bàn
          </button>
        </template>

      </div>

      <!-- Right: players sidebar -->
      <div class="bingo-sidebar">
        <div class="bingo-sidebar-title">Người chơi</div>
        <div class="bingo-player-list">
          <div v-for="p in playersInGame" :key="p.id" class="bingo-player-row">
            <span class="bingo-player-name" :class="{me: p.id === userId}">{{ p.name }}</span>
            <span v-if="p.is_winner" class="badge badge-warning">BINGO!</span>
            <span v-else-if="phase === 'lobby'" :class="['badge', p.is_ready ? 'badge-success' : 'badge-secondary']">
              {{ p.is_ready ? '✓' : '…' }}
            </span>
            <span v-else class="bingo-mark-count">{{ p.mark_count }}<small>/25</small></span>
          </div>
        </div>
      </div>

    </div><!-- /bingo-layout -->

    <!-- Error toast -->
    <div v-if="errMsg" class="bingo-error-toast" @click="errMsg = ''">
      <i class="fas fa-exclamation-circle mr-1"></i>{{ errMsg }}
    </div>

  </div>
</template>

<script>
export default {
  name: 'BingoRoom',
  props: {
    tableId:   { type: Number, required: true },
    tableName: { type: String, required: true },
    entryFee:  { type: Number, default: 50 },
    userId:    { type: Number, required: true },
    userName:  { type: String, required: true },
    routes:    { type: Object, required: true },
    csrf:      { type: String, required: true },
  },

  data() {
    return {
      phase:         'lobby',
      players:       [],
      playersInGame: [],
      myCard:        null,
      calledNumbers: [],
      winnerIds:     [],
      pot:           0,
      minPlayers:    2,

      isReady:       false,
      toggling:      false,
      showWinner:    false,
      justCalled:    false,
      justCalledNum: null,
      errMsg:        '',

      _channel:   null,
      _pollTimer: null,
    };
  },

  computed: {
    markedSet() { return new Set(this.calledNumbers); },
    markCount() {
      if (!this.myCard) return 1;
      let n = 0;
      for (let r = 0; r < 5; r++)
        for (let c = 0; c < 5; c++)
          if (this.isCellMarked(r, c)) n++;
      return n;
    },
    lastCalled() {
      return this.calledNumbers.length ? this.calledNumbers[this.calledNumbers.length - 1] : null;
    },
    recentCalled() {
      const len = this.calledNumbers.length;
      return this.calledNumbers.slice(Math.max(0, len - 6), len - 1).reverse();
    },
    isWinner()    { return this.winnerIds.includes(this.userId); },
    winnerNames() { return this.playersInGame.filter(p => this.winnerIds.includes(p.id)).map(p => p.name); },
    readyCount()  { return this.players.filter(p => p.is_ready).length; },
  },

  mounted() {
    this.fetchState();
    this._pollTimer = setInterval(() => this.fetchState(), 10000);
    this.subscribeChannel();
  },

  beforeUnmount() {
    clearInterval(this._pollTimer);
    if (window.Echo) window.Echo.leaveChannel(`bingo.${this.tableId}`);
  },

  methods: {
    pad(n) { return String(n).padStart(2, '0'); },
    isFree(r, c) { return r === 2 && c === 2; },
    isCellMarked(r, c) {
      if (this.isFree(r, c)) return true;
      if (!this.myCard) return false;
      return this.markedSet.has(this.myCard[r][c]);
    },

    subscribeChannel() {
      if (!window.Echo) return;
      this._channel = window.Echo.channel(`bingo.${this.tableId}`)
        .listen('BingoRoomUpdated', (e) => this.handleEvent(e));
    },

    handleEvent(e) {
      if (e.type === 'ready_update' || e.type === 'player_joined') {
        this.players = e.players || [];
        const me = this.players.find(p => p.id === this.userId);
        if (me) this.isReady = me.is_ready;
        this.syncPlayersInGame();
      } else if (e.type === 'game_started') {
        this.fetchState();
      } else if (e.type === 'number_called') {
        this.calledNumbers = e.called_numbers || [...this.calledNumbers, e.number];
        this.justCalledNum = e.number;
        this.justCalled = true;
        setTimeout(() => { this.justCalled = false; }, 800);
      } else if (e.type === 'game_finished') {
        this.winnerIds  = e.winner_ids || [];
        this.pot        = e.pot || this.pot;
        this.phase      = 'finished';
        this.showWinner = true;
        this.fetchState();
      }
    },

    async fetchState() {
      try {
        const res  = await fetch(this.routes.state, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        this.applyState(data);
      } catch (_) {}
    },

    applyState(data) {
      if (data.players) {
        this.players = data.players;
        const me = data.players.find(p => p.id === this.userId);
        if (me) this.isReady = me.is_ready;
      }
      if (data.game) {
        const g = data.game;
        this.phase         = g.phase;
        this.myCard        = g.my_card;
        this.calledNumbers = g.called_numbers || [];
        this.playersInGame = g.players || [];
        this.winnerIds     = g.winner_ids || [];
        this.pot           = g.pot || 0;
        if (g.phase === 'finished' && this.winnerIds.length && !this.showWinner) {
          this.showWinner = true;
        }
      } else {
        this.phase = 'lobby';
        this.syncPlayersInGame();
      }
    },

    syncPlayersInGame() {
      this.playersInGame = this.players.map(p => ({
        id: p.id, name: p.name, is_ready: p.is_ready, is_winner: false, mark_count: 0,
      }));
    },

    async toggleReady() {
      this.toggling = true;
      try {
        const res = await fetch(this.routes.ready, {
          method: 'POST',
          headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
          body: '{}',
        });
        const data = await res.json();
        if (data.error) { this.errMsg = data.error; return; }
        if (data.game_started) {
          this.applyState({ game: data.state, players: this.players });
        } else if (data.players) {
          this.players = data.players;
          const me = data.players.find(p => p.id === this.userId);
          if (me) this.isReady = me.is_ready;
          this.syncPlayersInGame();
        }
      } catch (_) {
        this.errMsg = 'Lỗi kết nối.';
      } finally {
        this.toggling = false;
      }
    },

    leaveTable() {
      if (!confirm('Rời bàn?')) return;
      const form   = document.createElement('form');
      form.method  = 'POST';
      form.action  = this.routes.leave;
      const csrf   = document.createElement('input');
      csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = this.csrf;
      const method = document.createElement('input');
      method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
      form.appendChild(csrf); form.appendChild(method);
      document.body.appendChild(form); form.submit();
    },
  },
};
</script>

<style scoped>
.bingo-wrap { min-height: 100vh; padding: 0 0 40px; color: #e0e0e0; }

.bingo-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 20px; margin-bottom: 20px;
  background: rgba(0,0,0,.3); border-radius: 12px; flex-wrap: wrap; gap: 8px;
}
.bingo-title { font-size: 18px; font-weight: 900; color: #fff; }

.bingo-layout { display: grid; grid-template-columns: 1fr 220px; gap: 20px; align-items: start; }
@media (max-width: 768px) { .bingo-layout { grid-template-columns: 1fr; } }

.bingo-lobby {
  background: linear-gradient(145deg,#021a1a,#0a4a4a);
  border-radius: 16px; padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,.4);
}
.bingo-lobby-title { font-size: 20px; font-weight: 900; color: #fff; margin-bottom: 4px; }
.bingo-lobby-sub { font-size: 12px; color: rgba(255,255,255,.4); margin-bottom: 20px; }
.bingo-ready-status { text-align: center; }

.bingo-player-list { display: flex; flex-direction: column; gap: 6px; }
.bingo-player-row {
  display: flex; align-items: center; justify-content: space-between;
  padding: 6px 10px; border-radius: 8px; background: rgba(0,0,0,.2); font-size: 13px;
}
.bingo-player-name { color: #fff; font-weight: 600; }
.bingo-player-name.me { color: #1abc9c; }

.bingo-last-called {
  text-align: center; margin-bottom: 20px;
  background: rgba(0,0,0,.3); border-radius: 14px; padding: 16px;
}
.bingo-ball-big {
  width: 80px; height: 80px; border-radius: 50%;
  background: linear-gradient(135deg,#1abc9c,#0e6655);
  color: #fff; font-size: 28px; font-weight: 900;
  display: inline-flex; align-items: center; justify-content: center;
  margin-bottom: 10px; box-shadow: 0 0 20px rgba(26,188,156,.5);
}
.bingo-ball-big.pulse { animation: ballPulse .6s ease-out; }
@keyframes ballPulse {
  0%   { transform: scale(1);   box-shadow: 0 0 20px rgba(26,188,156,.5); }
  40%  { transform: scale(1.3); box-shadow: 0 0 40px rgba(26,188,156,.9); }
  100% { transform: scale(1);   box-shadow: 0 0 20px rgba(26,188,156,.5); }
}
.bingo-called-row { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
.bingo-ball-sm {
  width: 32px; height: 32px; border-radius: 50%;
  background: rgba(26,188,156,.2); border: 1px solid rgba(26,188,156,.4);
  color: #1abc9c; font-size: 11px; font-weight: 700;
  display: inline-flex; align-items: center; justify-content: center;
}

.bingo-card-wrap {
  background: rgba(0,0,0,.3); border-radius: 14px; padding: 16px;
  box-shadow: 0 4px 20px rgba(0,0,0,.4);
}
.bingo-card-header { display: grid; grid-template-columns: repeat(5,1fr); gap: 4px; margin-bottom: 4px; }
.bingo-col-label { text-align: center; font-size: 16px; font-weight: 900; color: #1abc9c; letter-spacing: 2px; }
.bingo-card-grid { display: grid; grid-template-columns: repeat(5,1fr); gap: 4px; }
.bingo-cell {
  aspect-ratio: 1; border-radius: 8px;
  background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 13px; font-weight: 700; color: rgba(255,255,255,.5);
  transition: all .2s;
}
.bingo-cell.marked { background: rgba(26,188,156,.25); border-color: #1abc9c; color: #fff; }
.bingo-cell.free   { background: rgba(26,188,156,.35); border-color: #1abc9c; color: #1abc9c; }
.bingo-cell.just-called { animation: cellFlash .5s ease-out; }
@keyframes cellFlash {
  0%   { background: rgba(246,194,62,.8); border-color: #f6c23e; color: #000; }
  100% { background: rgba(26,188,156,.25); border-color: #1abc9c; color: #fff; }
}
.bingo-free { font-size: 9px; font-weight: 900; letter-spacing: .5px; }

.bingo-sidebar { background: rgba(0,0,0,.25); border-radius: 14px; padding: 16px; }
.bingo-sidebar-title {
  font-size: 11px; text-transform: uppercase; letter-spacing: .8px;
  color: rgba(255,255,255,.35); margin-bottom: 10px;
}
.bingo-mark-count { font-size: 13px; font-weight: 700; color: #1abc9c; }
.bingo-mark-count small { color: rgba(255,255,255,.3); font-size: 10px; }

.bingo-winner-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.7);
  display: flex; align-items: center; justify-content: center; z-index: 9999; cursor: pointer;
}
.bingo-winner-box {
  background: linear-gradient(145deg,#1a2a0a,#2d5a10);
  border: 2px solid #2ecc71; border-radius: 20px; padding: 36px 48px;
  text-align: center; box-shadow: 0 0 60px rgba(46,204,113,.5);
  animation: winnerPop .4s cubic-bezier(.34,1.56,.64,1);
}
@keyframes winnerPop {
  from { transform: scale(.5); opacity: 0; }
  to   { transform: scale(1);  opacity: 1; }
}
.bingo-winner-title { font-size: 40px; font-weight: 900; color: #f6c23e; margin-bottom: 10px; }
.bingo-winner-me    { font-size: 18px; font-weight: 700; color: #2ecc71; margin-bottom: 8px; }
.bingo-winner-other { font-size: 16px; color: #fff; margin-bottom: 8px; }
.bingo-winner-pot   { font-size: 24px; font-weight: 900; color: #f6c23e; margin-bottom: 12px; }

.bingo-error-toast {
  position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
  background: #c0392b; color: #fff; padding: 10px 20px; border-radius: 8px;
  font-size: 13px; cursor: pointer; z-index: 9999; box-shadow: 0 4px 16px rgba(0,0,0,.4);
}
</style>
