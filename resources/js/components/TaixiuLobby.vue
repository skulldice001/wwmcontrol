<template>
  <div>
    <!-- Header -->
    <div class="row mb-3">
      <div class="col-12">
        <a :href="routes.entertainment" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> {{ msg.entertainmentHall }}
        </a>
        <button data-toggle="modal" data-target="#tx-create-modal" class="btn btn-warning float-right ml-2">
          <i class="fas fa-plus"></i> {{ msg.txCreateTable }}
        </button>
      </div>
    </div>

    <!-- Table list -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">{{ msg.txLobby }}</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped projects mb-0">
            <thead>
              <tr>
                <th>{{ msg.tableName }}</th>
                <th>{{ msg.minBet }} / {{ msg.maxBet }}</th>
                <th>{{ msg.players }}</th>
                <th class="text-center">{{ msg.status }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in tables" :key="t.id">
                <td><strong>{{ t.name }}</strong></td>
                <td>{{ fmt(t.min_bet) }} / {{ fmt(t.max_bet) }} Zoo</td>
                <td>{{ t.current_players }} / {{ t.max_players }}</td>
                <td class="text-center">
                  <span :class="statusBadge(t.status)">{{ statusLabel(t.status) }}</span>
                </td>
                <td class="text-right">
                  <button
                    v-if="t.current_players < t.max_players"
                    class="btn btn-warning btn-sm"
                    @click="joinTable(t)"
                  >
                    <i class="fas fa-play"></i> {{ msg.txJoin }}
                  </button>
                  <button v-else class="btn btn-secondary btn-sm" disabled>
                    <i class="fas fa-ban"></i> {{ msg.full }}
                  </button>
                </td>
              </tr>
              <tr v-if="tables.length === 0">
                <td colspan="5" class="text-center text-muted py-4">{{ msg.noTables }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create Modal -->
    <div class="modal fade" id="tx-create-modal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-plus mr-1"></i> {{ msg.txCreateTable }}</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">
            <div class="form-group">
              <label>{{ msg.tableName }}</label>
              <input type="text" class="form-control" v-model="form.name" placeholder="My Tài Xỉu Table" maxlength="60">
            </div>
            <div class="form-row">
              <div class="form-group col-6">
                <label>{{ msg.minBet }} (Zoo)</label>
                <input type="number" class="form-control" v-model.number="form.min_bet" min="1">
              </div>
              <div class="form-group col-6">
                <label>{{ msg.maxBet }} (Zoo)</label>
                <input type="number" class="form-control" v-model.number="form.max_bet" min="1">
              </div>
            </div>
            <div class="form-group">
              <label>{{ msg.maxPlayers }}</label>
              <input type="number" class="form-control" v-model.number="form.max_players" min="2" max="100">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ msg.cancel }}</button>
            <button type="button" class="btn btn-warning" @click="createTable" :disabled="creating">
              <span v-if="creating" class="spinner-border spinner-border-sm mr-1"></span>
              <i v-else class="fas fa-plus mr-1"></i> {{ msg.txCreateTable }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading overlay -->
    <div v-if="joining" style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.6);display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;">
      <div class="spinner-border mb-3" style="width:3rem;height:3rem;"></div>
      <h5>{{ msg.joining }}</h5>
    </div>
  </div>
</template>

<script>
export default {
    name: 'TaixiuLobby',

    props: {
        initTables: { type: Array,  default: () => [] },
        routes:     { type: Object, required: true },
        csrf:       { type: String, required: true },
        msg:        { type: Object, required: true },
    },

    data() {
        return {
            tables:   [...this.initTables],
            form:     { name: '', min_bet: 100, max_bet: 5000, max_players: 20 },
            creating: false,
            joining:  false,
        };
    },

    mounted() {
        if (typeof window.initEcho === 'function') window.initEcho();
        if (window.Echo) {
            window.Echo.channel('taixiu.lobby')
                .listen('TaixiuTableUpdated', e => {
                    if (e.table) this.updateRow(e.table);
                });
        }
    },

    beforeUnmount() {
        if (window.Echo) window.Echo.leave('taixiu.lobby');
    },

    methods: {
        fmt(n) { return Number(n).toLocaleString(); },

        statusLabel(s) {
            return { playing: this.msg.playing, waiting: this.msg.waiting, full: this.msg.full }[s] || s;
        },
        statusBadge(s) {
            return { playing: 'badge badge-warning', waiting: 'badge badge-success', full: 'badge badge-danger' }[s] || 'badge badge-secondary';
        },

        updateRow(table) {
            const idx = this.tables.findIndex(t => t.id === table.id);
            if (table.status === 'closed') {
                if (idx >= 0) this.tables.splice(idx, 1);
            } else if (idx >= 0) {
                this.tables.splice(idx, 1, table);
            } else {
                this.tables.push(table);
            }
        },

        joinTable(t) {
            this.joining = true;
            fetch(`${this.routes.joinBase}/${t.id}/join`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(res => {
                    if (res.redirect) { window.location.href = res.redirect; }
                    else { this.joining = false; window.notify && window.notify('error', res.message || this.msg.failJoin); }
                })
                .catch(() => { this.joining = false; window.notify && window.notify('error', this.msg.failJoin); });
        },

        createTable() {
            if (!this.form.name.trim()) { window.notify && window.notify('warning', this.msg.enterName); return; }
            this.creating = true;
            fetch(this.routes.create, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                body: JSON.stringify(this.form),
            })
                .then(r => r.json())
                .then(res => {
                    if (res.redirect) { window.location.href = res.redirect; }
                    else {
                        this.creating = false;
                        const err = res.errors ? Object.values(res.errors).flat().join(' ') : (res.message || this.msg.failCreate);
                        window.notify && window.notify('error', err);
                    }
                })
                .catch(() => { this.creating = false; });
        },
    },
};
</script>
