<template>
  <div class="lottery-wrap">

    <!-- Header -->
    <div class="lottery-header">
      <a :href="routes.back" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Sảnh Giải Trí
      </a>
      <div class="text-center">
        <span class="lottery-title"><i class="fas fa-ticket-alt mr-2" style="color:#f6c23e;"></i>Xổ Số Zoo</span>
      </div>
      <div class="lottery-balance">
        <i class="fas fa-coins mr-1" style="color:#f6c23e;"></i>
        <span style="color:#f6c23e;font-weight:800;">{{ balance.toLocaleString() }}</span>
        <small class="text-muted ml-1">Zoo</small>
      </div>
    </div>

    <!-- Two draw cards -->
    <div class="lottery-cards">

      <!-- Daily draw -->
      <div class="lottery-card lottery-card-daily">
        <div class="lottery-card-stripe"></div>
        <div class="lottery-card-body">
          <div class="lottery-badge">Hàng Ngày · 20:00</div>
          <div class="lottery-mult">×{{ daily.multiplier }}</div>
          <div class="lottery-label">Rút {{ daily.pick_count }} số · Phạm vi 1–45</div>

          <!-- Countdown -->
          <div class="lottery-countdown" v-if="daily.status === 'open'">
            <i class="fas fa-clock mr-1"></i>
            <span class="lottery-countdown-val">{{ fmtCountdown(dailySeconds) }}</span>
          </div>
          <div class="lottery-drawn-badge" v-else-if="daily.status !== 'open'">
            <i class="fas fa-check-circle mr-1"></i>
            Đã quay: <strong>{{ (daily.winning_numbers || []).map(n => pad(n)).join(' · ') }}</strong>
          </div>

          <!-- Pot info -->
          <div class="lottery-pot-info">
            <div>
              <span class="text-muted" style="font-size:11px;">Tổng vé</span>
              <div style="font-size:15px;font-weight:700;">{{ daily.total_tickets }}</div>
            </div>
            <div>
              <span class="text-muted" style="font-size:11px;">Tổng cược</span>
              <div style="font-size:15px;font-weight:700;color:#f6c23e;">{{ daily.total_pot.toLocaleString() }} Zoo</div>
            </div>
          </div>

          <!-- Ticket form -->
          <div v-if="daily.status === 'open'" class="lottery-form">
            <div class="lottery-number-grid">
              <button
                v-for="n in 45" :key="n"
                :class="['lottery-num-btn', dailyPick === n ? 'selected' : '']"
                @click="dailyPick = n"
              >{{ pad(n) }}</button>
            </div>
            <div class="lottery-bet-row mt-2">
              <input
                type="number" v-model.number="dailyBet" :min="10" step="10"
                class="form-control form-control-sm lottery-bet-input"
                placeholder="Số Zoo cược"
              >
              <button
                class="btn btn-warning btn-sm lottery-buy-btn"
                :disabled="!dailyPick || dailyBet < 10 || buying === 'daily'"
                @click="buyTicket('daily')"
              >
                <span v-if="buying === 'daily'"><i class="fas fa-spinner fa-spin mr-1"></i></span>
                <span v-else><i class="fas fa-ticket-alt mr-1"></i></span>
                Mua vé
              </button>
            </div>
            <div v-if="dailyPick" class="text-center mt-1" style="font-size:11px;color:#aaa;">
              Số đã chọn: <strong style="color:#f6c23e;">{{ pad(dailyPick) }}</strong>
              · Cược {{ dailyBet || 0 }} Zoo → Thắng {{ ((dailyBet || 0) * daily.multiplier).toLocaleString() }} Zoo
            </div>
          </div>

          <!-- My tickets -->
          <div v-if="daily.my_tickets && daily.my_tickets.length" class="lottery-my-tickets">
            <div class="lottery-my-tickets-title">Vé của bạn:</div>
            <div v-for="t in daily.my_tickets" :key="t.id" class="lottery-ticket-row">
              <span class="lottery-ticket-num">{{ pad(t.picked_number) }}</span>
              <span class="text-muted">{{ t.bet_amount.toLocaleString() }} Zoo</span>
              <span v-if="t.is_winner === true"  class="badge badge-success ml-auto">+{{ t.payout.toLocaleString() }}</span>
              <span v-else-if="t.is_winner === false" class="badge badge-danger ml-auto">Trượt</span>
              <span v-else class="badge badge-secondary ml-auto">Chờ</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Weekly draw -->
      <div class="lottery-card lottery-card-weekly">
        <div class="lottery-card-stripe"></div>
        <div class="lottery-card-body">
          <div class="lottery-badge weekly">Hàng Tuần · Thứ 7 · 21:00</div>
          <div class="lottery-mult weekly">×{{ weekly.multiplier }}</div>
          <div class="lottery-label">Rút {{ weekly.pick_count }} số · Phạm vi 1–45</div>

          <!-- Countdown -->
          <div class="lottery-countdown" v-if="weekly.status === 'open'">
            <i class="fas fa-clock mr-1"></i>
            <span class="lottery-countdown-val">{{ fmtCountdown(weeklySeconds) }}</span>
          </div>
          <div class="lottery-drawn-badge" v-else-if="weekly.status !== 'open'">
            <i class="fas fa-check-circle mr-1"></i>
            Đã quay: <strong>{{ (weekly.winning_numbers || []).map(n => pad(n)).join(' · ') }}</strong>
          </div>

          <!-- Pot info -->
          <div class="lottery-pot-info">
            <div>
              <span class="text-muted" style="font-size:11px;">Tổng vé</span>
              <div style="font-size:15px;font-weight:700;">{{ weekly.total_tickets }}</div>
            </div>
            <div>
              <span class="text-muted" style="font-size:11px;">Tổng cược</span>
              <div style="font-size:15px;font-weight:700;color:#f6c23e;">{{ weekly.total_pot.toLocaleString() }} Zoo</div>
            </div>
          </div>

          <!-- Ticket form -->
          <div v-if="weekly.status === 'open'" class="lottery-form">
            <div class="lottery-number-grid">
              <button
                v-for="n in 45" :key="n"
                :class="['lottery-num-btn', weeklyPick === n ? 'selected' : '']"
                @click="weeklyPick = n"
              >{{ pad(n) }}</button>
            </div>
            <div class="lottery-bet-row mt-2">
              <input
                type="number" v-model.number="weeklyBet" :min="10" step="10"
                class="form-control form-control-sm lottery-bet-input"
                placeholder="Số Zoo cược"
              >
              <button
                class="btn btn-sm lottery-buy-btn weekly"
                :disabled="!weeklyPick || weeklyBet < 10 || buying === 'weekly'"
                @click="buyTicket('weekly')"
              >
                <span v-if="buying === 'weekly'"><i class="fas fa-spinner fa-spin mr-1"></i></span>
                <span v-else><i class="fas fa-ticket-alt mr-1"></i></span>
                Mua vé
              </button>
            </div>
            <div v-if="weeklyPick" class="text-center mt-1" style="font-size:11px;color:#aaa;">
              Số đã chọn: <strong style="color:#c084fc;">{{ pad(weeklyPick) }}</strong>
              · Cược {{ weeklyBet || 0 }} Zoo → Thắng {{ ((weeklyBet || 0) * weekly.multiplier).toLocaleString() }} Zoo
            </div>
          </div>

          <!-- My tickets -->
          <div v-if="weekly.my_tickets && weekly.my_tickets.length" class="lottery-my-tickets">
            <div class="lottery-my-tickets-title">Vé của bạn:</div>
            <div v-for="t in weekly.my_tickets" :key="t.id" class="lottery-ticket-row">
              <span class="lottery-ticket-num weekly">{{ pad(t.picked_number) }}</span>
              <span class="text-muted">{{ t.bet_amount.toLocaleString() }} Zoo</span>
              <span v-if="t.is_winner === true"  class="badge badge-success ml-auto">+{{ t.payout.toLocaleString() }}</span>
              <span v-else-if="t.is_winner === false" class="badge badge-danger ml-auto">Trượt</span>
              <span v-else class="badge badge-secondary ml-auto">Chờ</span>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /lottery-cards -->

    <!-- Recent results -->
    <div class="lottery-history">
      <div class="lottery-history-col">
        <h6 class="lottery-history-title"><i class="fas fa-history mr-1"></i> Kết quả ngày gần đây</h6>
        <table class="table table-sm table-dark lottery-table">
          <thead><tr><th>Ngày</th><th>Số trúng</th><th>Tổng vé</th><th>Thưởng</th></tr></thead>
          <tbody>
            <tr v-if="!recentDaily.length"><td colspan="4" class="text-center text-muted">Chưa có kết quả</td></tr>
            <tr v-for="d in recentDaily" :key="d.id">
              <td>{{ d.draw_at }}</td>
              <td><span v-for="n in d.winning_numbers" :key="n" class="lottery-result-num">{{ pad(n) }}</span></td>
              <td>{{ d.total_tickets }}</td>
              <td style="color:#f6c23e;">{{ d.total_payout.toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="lottery-history-col">
        <h6 class="lottery-history-title"><i class="fas fa-history mr-1"></i> Kết quả tuần gần đây</h6>
        <table class="table table-sm table-dark lottery-table">
          <thead><tr><th>Tuần</th><th>Số trúng</th><th>Tổng vé</th><th>Thưởng</th></tr></thead>
          <tbody>
            <tr v-if="!recentWeekly.length"><td colspan="4" class="text-center text-muted">Chưa có kết quả</td></tr>
            <tr v-for="d in recentWeekly" :key="d.id">
              <td>{{ d.draw_at }}</td>
              <td><span v-for="n in d.winning_numbers" :key="n" class="lottery-result-num weekly">{{ pad(n) }}</span></td>
              <td>{{ d.total_tickets }}</td>
              <td style="color:#f6c23e;">{{ d.total_payout.toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Error toast -->
    <div v-if="errMsg" class="lottery-error-toast" @click="errMsg = ''">
      <i class="fas fa-exclamation-circle mr-1"></i>{{ errMsg }}
    </div>

  </div>
</template>

<script>
export default {
  name: 'LotteryLobby',
  props: {
    initDaily:        { type: Object, required: true },
    initWeekly:       { type: Object, required: true },
    initRecentDaily:  { type: Array,  default: () => [] },
    initRecentWeekly: { type: Array,  default: () => [] },
    initBalance:      { type: Number, default: 0 },
    routes:           { type: Object, required: true },
    csrf:             { type: String, required: true },
  },

  data() {
    return {
      daily:        { ...this.initDaily },
      weekly:       { ...this.initWeekly },
      recentDaily:  [...this.initRecentDaily],
      recentWeekly: [...this.initRecentWeekly],
      balance:      this.initBalance,

      dailySeconds:  this.initDaily.seconds_left  || 0,
      weeklySeconds: this.initWeekly.seconds_left || 0,

      dailyPick:  null,
      weeklyPick: null,
      dailyBet:   100,
      weeklyBet:  100,

      buying:  null, // 'daily' | 'weekly' | null
      errMsg:  '',

      _ticker:  null,
      _poller:  null,
    };
  },

  mounted() {
    this._ticker = setInterval(() => {
      if (this.dailySeconds > 0)  this.dailySeconds--;
      if (this.weeklySeconds > 0) this.weeklySeconds--;
    }, 1000);

    this._poller = setInterval(() => this.poll(), 30000);
  },

  beforeUnmount() {
    clearInterval(this._ticker);
    clearInterval(this._poller);
  },

  methods: {
    pad(n) {
      return String(n).padStart(2, '0');
    },

    fmtCountdown(sec) {
      if (sec <= 0) return 'Đang quay...';
      const d = Math.floor(sec / 86400);
      const h = Math.floor((sec % 86400) / 3600);
      const m = Math.floor((sec % 3600) / 60);
      const s = sec % 60;
      if (d > 0) return `${d}n ${h}g ${String(m).padStart(2,'0')}p`;
      if (h > 0) return `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
      return `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    },

    async poll() {
      try {
        const res = await fetch(this.routes.state, {
          headers: { 'Accept': 'application/json' },
        });
        const data = await res.json();
        this.applyState(data);
      } catch (_) {}
    },

    applyState(data) {
      this.balance      = data.balance;
      this.recentDaily  = data.recent_daily  || [];
      this.recentWeekly = data.recent_weekly || [];

      if (data.daily) {
        this.daily        = data.daily;
        this.dailySeconds = data.daily.seconds_left || 0;
      }
      if (data.weekly) {
        this.weekly        = data.weekly;
        this.weeklySeconds = data.weekly.seconds_left || 0;
      }
    },

    async buyTicket(type) {
      const pick = type === 'daily' ? this.dailyPick : this.weeklyPick;
      const bet  = type === 'daily' ? this.dailyBet  : this.weeklyBet;
      const draw = type === 'daily' ? this.daily      : this.weekly;

      if (!pick || bet < 10) {
        this.errMsg = 'Vui lòng chọn số và nhập số Zoo cược (tối thiểu 10).';
        return;
      }
      if (bet > this.balance) {
        this.errMsg = 'Không đủ Zoo.';
        return;
      }

      this.buying = type;
      try {
        const res = await fetch(this.routes.ticket, {
          method:  'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept':        'application/json',
            'X-CSRF-TOKEN':  this.csrf,
          },
          body: JSON.stringify({
            draw_id:        draw.id,
            picked_number:  pick,
            bet_amount:     bet,
          }),
        });
        const data = await res.json();
        if (data.error) {
          this.errMsg = data.error;
          return;
        }
        // Update balance and tickets
        this.balance = data.balance;
        if (type === 'daily') {
          this.daily.my_tickets = [...(this.daily.my_tickets || []), data.ticket];
          this.daily.total_tickets++;
          this.daily.total_pot += bet;
          this.dailyPick = null;
        } else {
          this.weekly.my_tickets = [...(this.weekly.my_tickets || []), data.ticket];
          this.weekly.total_tickets++;
          this.weekly.total_pot += bet;
          this.weeklyPick = null;
        }
      } catch (_) {
        this.errMsg = 'Lỗi kết nối. Thử lại sau.';
      } finally {
        this.buying = null;
      }
    },
  },
};
</script>

<style scoped>
.lottery-wrap {
  min-height: 100vh;
  padding: 0 0 40px;
  color: #e0e0e0;
}

/* Header */
.lottery-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 20px; margin-bottom: 20px;
  background: rgba(0,0,0,.3); border-radius: 12px;
}
.lottery-title {
  font-size: 22px; font-weight: 900; color: #fff; letter-spacing: 1px;
}
.lottery-balance { font-size: 14px; }

/* Cards row */
.lottery-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 28px;
}
@media (max-width: 768px) {
  .lottery-cards { grid-template-columns: 1fr; }
}

/* Card */
.lottery-card {
  border-radius: 16px; overflow: hidden;
  box-shadow: 0 8px 32px rgba(0,0,0,.5);
  position: relative;
}
.lottery-card-daily  { background: linear-gradient(145deg, #1a2a0a 0%, #2d5a10 55%, #1a2a0a 100%); }
.lottery-card-weekly { background: linear-gradient(145deg, #1a0a2e 0%, #3b1068 55%, #1a0a2e 100%); }

.lottery-card-stripe {
  height: 3px; width: 100%; position: absolute; top: 0;
}
.lottery-card-daily  .lottery-card-stripe { background: linear-gradient(90deg,#1e8449,#2ecc71,#1e8449); }
.lottery-card-weekly .lottery-card-stripe { background: linear-gradient(90deg,#6c3483,#a855f7,#6c3483); }

.lottery-card-body { padding: 22px 20px 20px; }

.lottery-badge {
  display: inline-block; padding: 3px 10px; border-radius: 20px;
  font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
  margin-bottom: 6px;
  background: rgba(46,204,113,.2); color: #2ecc71; border: 1px solid #2ecc7155;
}
.lottery-badge.weekly {
  background: rgba(168,85,247,.2); color: #c084fc; border: 1px solid #a855f755;
}

.lottery-mult {
  font-size: 52px; font-weight: 900; color: #f6c23e;
  line-height: 1; margin-bottom: 4px; letter-spacing: -1px;
  text-shadow: 0 2px 12px rgba(246,194,62,.4);
}
.lottery-mult.weekly { color: #c084fc; text-shadow: 0 2px 12px rgba(192,132,252,.4); }

.lottery-label { font-size: 11px; color: rgba(255,255,255,.35); margin-bottom: 12px; }

/* Countdown */
.lottery-countdown {
  background: rgba(0,0,0,.4); border-radius: 8px;
  padding: 8px 14px; margin-bottom: 12px;
  font-size: 12px; color: rgba(255,255,255,.5);
}
.lottery-countdown-val {
  font-size: 20px; font-weight: 800; color: #f6c23e; letter-spacing: 1px;
  font-variant-numeric: tabular-nums;
}

.lottery-drawn-badge {
  background: rgba(46,204,113,.15); border: 1px solid #2ecc7155;
  border-radius: 8px; padding: 8px 14px; margin-bottom: 12px;
  font-size: 13px; color: #2ecc71;
}

/* Pot info */
.lottery-pot-info {
  display: flex; gap: 16px; margin-bottom: 14px;
  background: rgba(0,0,0,.25); border-radius: 8px; padding: 8px 12px;
}

/* Number grid */
.lottery-number-grid {
  display: grid; grid-template-columns: repeat(9, 1fr); gap: 4px;
  margin-bottom: 8px;
}
.lottery-num-btn {
  padding: 0; height: 30px; border-radius: 6px; font-size: 11px; font-weight: 700;
  border: 1px solid rgba(255,255,255,.15);
  background: rgba(255,255,255,.07); color: rgba(255,255,255,.6);
  cursor: pointer; transition: all .15s;
}
.lottery-num-btn:hover { background: rgba(255,255,255,.18); color: #fff; }
.lottery-num-btn.selected {
  background: #f6c23e; color: #000; border-color: #f6c23e;
  box-shadow: 0 0 8px rgba(246,194,62,.6);
}
.lottery-card-weekly .lottery-num-btn.selected {
  background: #a855f7; border-color: #a855f7; color: #fff;
  box-shadow: 0 0 8px rgba(168,85,247,.6);
}

/* Bet row */
.lottery-bet-row { display: flex; gap: 8px; align-items: center; }
.lottery-bet-input { flex: 1; background: rgba(0,0,0,.4) !important; color: #fff !important; border-color: rgba(255,255,255,.2) !important; }
.lottery-buy-btn { white-space: nowrap; }
.lottery-buy-btn.weekly { background: linear-gradient(90deg,#6c3483,#a855f7); color: #fff; border: none; }

/* My tickets */
.lottery-my-tickets { margin-top: 12px; border-top: 1px solid rgba(255,255,255,.08); padding-top: 10px; }
.lottery-my-tickets-title { font-size: 10px; color: rgba(255,255,255,.35); text-transform: uppercase; letter-spacing: .8px; margin-bottom: 6px; }
.lottery-ticket-row {
  display: flex; align-items: center; gap: 8px;
  padding: 4px 0; font-size: 12px;
  border-bottom: 1px solid rgba(255,255,255,.05);
}
.lottery-ticket-num {
  width: 28px; height: 28px; border-radius: 50%;
  background: #f6c23e; color: #000;
  font-size: 12px; font-weight: 800;
  display: inline-flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.lottery-ticket-num.weekly { background: #a855f7; color: #fff; }

/* History */
.lottery-history { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media (max-width: 768px) { .lottery-history { grid-template-columns: 1fr; } }

.lottery-history-title { font-size: 13px; font-weight: 700; color: rgba(255,255,255,.6); margin-bottom: 8px; }
.lottery-table { background: rgba(0,0,0,.2); border-radius: 8px; overflow: hidden; }
.lottery-table thead th { font-size: 10px; color: rgba(255,255,255,.35); border-color: rgba(255,255,255,.08); }
.lottery-table tbody td { font-size: 12px; border-color: rgba(255,255,255,.05); vertical-align: middle; }

.lottery-result-num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; border-radius: 50%;
  background: #f6c23e; color: #000;
  font-size: 11px; font-weight: 800; margin-right: 3px;
}
.lottery-result-num.weekly { background: #a855f7; color: #fff; }

/* Error toast */
.lottery-error-toast {
  position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
  background: #c0392b; color: #fff; padding: 10px 20px; border-radius: 8px;
  font-size: 13px; cursor: pointer; z-index: 9999;
  box-shadow: 0 4px 16px rgba(0,0,0,.4);
}
</style>
