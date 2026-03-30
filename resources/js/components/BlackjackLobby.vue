<template>
  <div>
    <!-- Loading overlay -->
    <div v-if="loading" class="bj-lobby-loading">
      <i class="fas fa-spinner fa-spin fa-3x text-white"></i>
    </div>

    <!-- Header -->
    <div class="row mb-3">
      <div class="col-12 d-flex align-items-center justify-content-between">
        <a :href="routes.entertainment" class="btn btn-secondary btn-sm">
          <i class="fas fa-arrow-left"></i> {{ msg.entertainmentHall }}
        </a>
        <h4 class="mb-0">{{ msg.bjLobby }}</h4>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-info btn-sm mr-2" @click="refresh" :disabled="refreshing">
            <i :class="['fas fa-sync-alt mr-1', refreshing ? 'fa-spin' : '']"></i>Làm mới danh sách bàn
          </button>
          <button class="btn btn-success btn-sm mr-2" @click="showCreateModal = true">
            <i class="fas fa-plus"></i> {{ msg.bjCreateTable }}
          </button>
          <button class="btn btn-warning btn-sm" @click="playVsAi" :disabled="creatingAi">
            <i class="fas fa-robot mr-1"></i>{{ creatingAi ? 'Đang tạo...' : 'Chơi vs AI' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Tables card -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped projects mb-0">
            <thead>
              <tr>
                <th>{{ msg.tableName }}</th>
                <th>{{ msg.bjBetRange }}</th>
                <th>{{ msg.players }}</th>
                <th class="text-center">{{ msg.status }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in tables" :key="t.id">
                <td>
                  <strong>{{ t.name }}</strong>
                  <span v-if="t.is_preset" class="bj-badge-preset ml-1">Official</span>
                  <br><small class="text-muted">Max {{ t.max_players }} players</small>
                </td>
                <td>
                  <span class="badge badge-warning text-dark">
                    {{ fmt(t.min_bet) }} – {{ fmt(t.max_bet) }} Zoo
                  </span>
                </td>
                <td class="project_progress">
                  <div class="progress progress-sm">
                    <div class="progress-bar bg-green" role="progressbar"
                         :style="{ width: playerPercent(t) + '%' }"></div>
                  </div>
                  <small>{{ t.current_players }} / {{ t.max_players }}</small>
                </td>
                <td class="text-center">
                  <span v-if="t.status === 'waiting'" class="badge badge-success">{{ msg.waiting }}</span>
                  <span v-else-if="t.status === 'full'" class="badge badge-danger">{{ msg.full }}</span>
                  <span v-else class="badge badge-warning">{{ msg.playing }}</span>
                </td>
                <td class="text-right">
                  <button class="btn btn-primary btn-sm"
                          :disabled="t.status === 'full' || joiningId === t.id"
                          @click="joinTable(t.id)">
                    <i class="fas fa-sign-in-alt mr-1"></i>{{ msg.bjJoin }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create Table Modal -->
    <div v-if="showCreateModal" class="bj-modal-backdrop" @click.self="showCreateModal = false">
      <div class="modal-dialog mt-5">
        <div class="modal-content" style="background:#1a2332;border:1px solid #2d3f55;">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">{{ msg.bjCreateTable }}</h5>
            <button type="button" class="close text-white" @click="showCreateModal = false">&times;</button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>{{ msg.tableName }}</label>
              <input type="text" v-model="form.name" class="form-control" maxlength="60" placeholder="My Table">
            </div>
            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label>{{ msg.minBuyIn }}</label>
                  <input type="number" v-model.number="form.min_bet" class="form-control" min="1">
                </div>
              </div>
              <div class="col-6">
                <div class="form-group">
                  <label>{{ msg.maxBuyIn }}</label>
                  <input type="number" v-model.number="form.max_bet" class="form-control" min="1">
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>{{ msg.maxPlayers }}</label>
              <select v-model.number="form.max_players" class="form-control">
                <option v-for="n in [2, 3, 4, 5, 6, 7]" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>
          </div>
          <div class="modal-footer border-secondary">
            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">
              {{ msg.cancel }}
            </button>
            <button type="button" class="btn btn-success" :disabled="creating" @click="createTable">
              <i class="fas fa-check mr-1"></i>{{ msg.bjCreateTable }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BlackjackLobby',

  props: {
    initTables: { type: Array, default: () => [] },
    routes:     { type: Object, required: true },
    csrf:       { type: String, required: true },
    msg:        { type: Object, required: true },
  },

  data() {
    return {
      tables:          [...this.initTables],
      loading:         false,
      refreshing:      false,
      joiningId:       null,
      showCreateModal: false,
      creating:        false,
      creatingAi:      false,
      form: {
        name:        '',
        min_bet:     100,
        max_bet:     1000,
        max_players: 5,
      },
    };
  },

  methods: {
    fmt(n) {
      return Number(n).toLocaleString();
    },

    playerPercent(t) {
      return t.max_players > 0 ? Math.round(t.current_players / t.max_players * 100) : 0;
    },

    joinTable(id) {
      this.joiningId = id;
      this.loading = true;
      fetch(`${this.routes.joinBase}/${id}/join`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
      })
        .then(r => r.json())
        .then(data => {
          if (data.redirect) { window.location = data.redirect; return; }
          if (data.message && window.notify) window.notify('error', data.message);
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.joiningId = null; this.loading = false; });
    },

    createTable() {
      const { name, min_bet, max_bet, max_players } = this.form;
      if (!name.trim()) {
        if (window.notify) window.notify('warning', 'Please enter a table name.');
        return;
      }
      if (!min_bet || !max_bet || max_bet < min_bet) {
        if (window.notify) window.notify('warning', 'Invalid bet range.');
        return;
      }

      this.creating = true;
      this.loading  = true;
      fetch(this.routes.create, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
        body: JSON.stringify({ name: name.trim(), min_bet, max_bet, max_players }),
      })
        .then(r => r.json())
        .then(data => {
          if (data.redirect) { window.location = data.redirect; return; }
          if (data.errors || data.message) {
            const m = data.message || Object.values(data.errors).flat().join(' ');
            if (window.notify) window.notify('error', m);
          }
        })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.creating = false; this.loading = false; });
    },

    playVsAi() {
      this.creatingAi = true;
      fetch(this.routes.createAi, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      })
        .then(r => r.json())
        .then(data => { if (data.redirect) window.location.href = data.redirect; })
        .catch(() => { if (window.notify) window.notify('error', 'Không thể tạo bàn AI.'); })
        .finally(() => { this.creatingAi = false; });
    },

    refresh() {
      this.refreshing = true;
      fetch(this.routes.list, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => { this.tables = data.tables || []; })
        .catch(() => { if (window.notify) window.notify('error', 'Connection error.'); })
        .finally(() => { this.refreshing = false; });
    },

    updateRow(t) {
      if (t.status === 'closed') {
        this.tables = this.tables.filter(r => r.id !== t.id);
        return;
      }
      const idx = this.tables.findIndex(r => r.id === t.id);
      if (idx === -1) {
        this.tables.push(t);
      } else {
        this.tables.splice(idx, 1, { ...this.tables[idx], ...t });
      }
    },
  },

  mounted() {
    if (typeof window.initEcho === 'function') window.initEcho();
    if (window.Echo) {
      window.Echo.channel('blackjack.lobby')
        .listen('BlackjackTableUpdated', e => {
          if (e.table) this.updateRow(e.table);
        });
    }
  },

  beforeUnmount() {
    if (window.Echo) window.Echo.leave('blackjack.lobby');
  },
};
</script>

<style scoped>
.bj-badge-preset {
  font-size: 10px;
  letter-spacing: 1px;
  text-transform: uppercase;
  background: rgba(39, 174, 96, .15);
  color: #2ecc71;
  border: 1px solid rgba(39, 174, 96, .3);
  border-radius: 10px;
  padding: 1px 7px;
}

.bj-lobby-loading {
  display: flex;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, .55);
  z-index: 9999;
  align-items: center;
  justify-content: center;
}

.bj-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, .6);
  z-index: 1050;
  display: flex;
  align-items: flex-start;
  justify-content: center;
}
</style>
