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
          <div class="lottery-badge">Hàng Ngày · Mua vé 0h–7h · Rút thưởng 8h</div>
          <div class="lottery-mult">×{{ daily.multiplier }}</div>
          <div class="lottery-label">Rút {{ daily.pick_count }} số · Phạm vi 01–99</div>

          <!-- Countdown -->
          <div class="lottery-countdown" v-if="daily.status === 'open' && dailyOpenSeconds === 0 && dailySeconds > 0">
            <i class="fas fa-clock mr-1"></i>
            <small style="color:rgba(255,255,255,.4);">Đóng bán vé:</small>
            <span class="lottery-countdown-val ml-1">{{ fmtCountdown(dailySeconds) }}</span>
          </div>
          <div class="lottery-countdown" v-else-if="daily.status === 'open' && dailyOpenSeconds === 0 && dailySeconds === 0">
            <i class="fas fa-hourglass-half mr-1" style="color:#f6c23e;"></i>
            <span style="color:rgba(255,255,255,.6);font-size:13px;">Đang chuẩn bị rút thưởng lúc 08:00...</span>
          </div>
          <div class="lottery-drawn-badge" v-else>
            <i class="fas fa-check-circle mr-1"></i>
            Đã quay: <strong>{{ (daily.winning_numbers || []).map(n => pad(n)).join(' · ') }}</strong>
          </div>

          <!-- Not yet open for ticket sales -->
          <div v-if="dailyOpenSeconds > 0" class="lottery-not-open-notice">
            <i class="fas fa-lock mr-1"></i>
            Mở bán vé lúc <strong>{{ fmtOpenTime(daily.opens_at) }}</strong>
            · còn <strong>{{ fmtCountdown(dailyOpenSeconds) }}</strong>
          </div>

          <!-- Post-draw results banner (shown while ticket sales are locked) -->
          <div
            v-if="dailyOpenSeconds > 0 && lastSettledDaily && !claimedDrawIds.includes(lastSettledDaily.id)"
            class="lottery-result-banner"
          >
            <div class="lottery-result-banner-title">
              <i class="fas fa-star mr-1" style="color:#f6c23e;"></i>
              Kết quả ngày {{ lastSettledDaily.draw_at }}
            </div>
            <div class="lottery-result-banner-nums">
              <span v-for="n in lastSettledDaily.winning_numbers" :key="n" class="lottery-result-num lg">{{ pad(n) }}</span>
            </div>
            <div v-if="lastSettledDaily.my_ticket">
              <div v-if="lastSettledDaily.my_ticket.is_winner" class="lottery-winner-row">
                <i class="fas fa-trophy mr-1" style="color:#f6c23e;"></i>
                Bạn trúng! Số <strong>{{ pad(lastSettledDaily.my_ticket.picked_number) }}</strong>
                · Nhận <strong style="color:#f6c23e;">+{{ (lastSettledDaily.my_ticket.payout || 0).toLocaleString() }} Zoo</strong>
                <button class="btn btn-warning btn-xs ml-2" @click="claimDraw(lastSettledDaily.id)">
                  <i class="fas fa-gift mr-1"></i>Lĩnh thưởng
                </button>
              </div>
              <div v-else-if="lastSettledDaily.my_ticket.is_winner === false" class="lottery-miss-row">
                <i class="fas fa-times-circle mr-1"></i>
                Số <strong>{{ pad(lastSettledDaily.my_ticket.picked_number) }}</strong> chưa trúng lần này.
                <button class="btn btn-secondary btn-xs ml-2" @click="claimDraw(lastSettledDaily.id)">
                  Đóng
                </button>
              </div>
            </div>
            <div v-else class="lottery-miss-row">
              Bạn chưa mua vé giải này.
              <button class="btn btn-secondary btn-xs ml-2" @click="claimDraw(lastSettledDaily.id)">Đóng</button>
            </div>
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

          <!-- Ticket form — hidden if already bought or not yet open -->
          <div v-if="daily.status === 'open' && !daily.my_tickets.length && dailyOpenSeconds === 0" class="lottery-form">
            <div class="lottery-number-grid">
              <button
                v-for="n in 99" :key="n"
                :class="['lottery-num-btn', dailyPick === n ? 'selected' : '']"
                @click="dailyPick = n"
              >{{ pad(n) }}</button>
            </div>
            <!-- Tier bet buttons -->
            <div class="lottery-tiers mt-2">
              <button
                v-for="t in BET_TIERS" :key="t"
                :class="['lottery-tier-btn', dailyBet === t ? 'selected' : '']"
                @click="dailyBet = t"
              >{{ t.toLocaleString() }}</button>
            </div>
            <div v-if="dailyPick" class="text-center mt-1" style="font-size:11px;color:#aaa;">
              Số: <strong style="color:#f6c23e;">{{ pad(dailyPick) }}</strong>
              · Cược <strong>{{ dailyBet.toLocaleString() }}</strong> Zoo
              → Thắng <strong style="color:#f6c23e;">{{ (dailyBet * daily.multiplier).toLocaleString() }}</strong> Zoo
            </div>
            <button
              class="btn btn-warning btn-sm lottery-buy-btn mt-2 w-100"
              :disabled="!dailyPick || buying === 'daily'"
              @click="buyTicket('daily')"
            >
              <span v-if="buying === 'daily'"><i class="fas fa-spinner fa-spin mr-1"></i>Đang mua...</span>
              <span v-else><i class="fas fa-ticket-alt mr-1"></i>Mua Vé</span>
            </button>
          </div>

          <!-- Already bought notice -->
          <div v-else-if="daily.status === 'open' && daily.my_tickets.length && dailyOpenSeconds === 0" class="lottery-bought-notice">
            <i class="fas fa-check-circle mr-1"></i> Bạn đã mua vé cho giải hôm nay.
          </div>

          <!-- My tickets for this draw -->
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
          <div class="lottery-label">Rút {{ weekly.pick_count }} số · Phạm vi 01–99</div>

          <!-- Countdown -->
          <div class="lottery-countdown" v-if="weekly.status === 'open' && weeklyOpenSeconds === 0">
            <i class="fas fa-clock mr-1"></i>
            <span class="lottery-countdown-val">{{ fmtCountdown(weeklySeconds) }}</span>
          </div>
          <div class="lottery-drawn-badge" v-else>
            <i class="fas fa-check-circle mr-1"></i>
            Đã quay: <strong>{{ (weekly.winning_numbers || []).map(n => pad(n)).join(' · ') }}</strong>
          </div>

          <!-- Not yet open for ticket sales -->
          <div v-if="weeklyOpenSeconds > 0" class="lottery-not-open-notice weekly">
            <i class="fas fa-lock mr-1"></i>
            Mở bán vé lúc <strong>{{ fmtOpenTime(weekly.opens_at) }}</strong>
            · còn <strong>{{ fmtCountdown(weeklyOpenSeconds) }}</strong>
          </div>

          <!-- Post-draw results banner (shown while ticket sales are locked) -->
          <div
            v-if="weeklyOpenSeconds > 0 && lastSettledWeekly && !claimedDrawIds.includes(lastSettledWeekly.id)"
            class="lottery-result-banner weekly"
          >
            <div class="lottery-result-banner-title">
              <i class="fas fa-star mr-1" style="color:#c084fc;"></i>
              Kết quả tuần {{ lastSettledWeekly.draw_at }}
            </div>
            <div class="lottery-result-banner-nums">
              <span v-for="n in lastSettledWeekly.winning_numbers" :key="n" class="lottery-result-num weekly lg">{{ pad(n) }}</span>
            </div>
            <div v-if="lastSettledWeekly.my_ticket">
              <div v-if="lastSettledWeekly.my_ticket.is_winner" class="lottery-winner-row weekly">
                <i class="fas fa-trophy mr-1" style="color:#c084fc;"></i>
                Bạn trúng! Số <strong>{{ pad(lastSettledWeekly.my_ticket.picked_number) }}</strong>
                · Nhận <strong style="color:#c084fc;">+{{ (lastSettledWeekly.my_ticket.payout || 0).toLocaleString() }} Zoo</strong>
                <button class="btn btn-xs ml-2" style="background:#a855f7;color:#fff;" @click="claimDraw(lastSettledWeekly.id)">
                  <i class="fas fa-gift mr-1"></i>Lĩnh thưởng
                </button>
              </div>
              <div v-else-if="lastSettledWeekly.my_ticket.is_winner === false" class="lottery-miss-row">
                <i class="fas fa-times-circle mr-1"></i>
                Số <strong>{{ pad(lastSettledWeekly.my_ticket.picked_number) }}</strong> chưa trúng lần này.
                <button class="btn btn-secondary btn-xs ml-2" @click="claimDraw(lastSettledWeekly.id)">Đóng</button>
              </div>
            </div>
            <div v-else class="lottery-miss-row">
              Bạn chưa mua vé giải này.
              <button class="btn btn-secondary btn-xs ml-2" @click="claimDraw(lastSettledWeekly.id)">Đóng</button>
            </div>
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

          <!-- Ticket form — hidden if already bought or not yet open -->
          <div v-if="weekly.status === 'open' && !weekly.my_tickets.length && weeklyOpenSeconds === 0" class="lottery-form">
            <div class="lottery-number-grid">
              <button
                v-for="n in 99" :key="n"
                :class="['lottery-num-btn', weeklyPick === n ? 'selected' : '']"
                @click="weeklyPick = n"
              >{{ pad(n) }}</button>
            </div>
            <!-- Tier bet buttons -->
            <div class="lottery-tiers mt-2">
              <button
                v-for="t in BET_TIERS" :key="t"
                :class="['lottery-tier-btn weekly', weeklyBet === t ? 'selected' : '']"
                @click="weeklyBet = t"
              >{{ t.toLocaleString() }}</button>
            </div>
            <div v-if="weeklyPick" class="text-center mt-1" style="font-size:11px;color:#aaa;">
              Số: <strong style="color:#c084fc;">{{ pad(weeklyPick) }}</strong>
              · Cược <strong>{{ weeklyBet.toLocaleString() }}</strong> Zoo
              → Thắng <strong style="color:#c084fc;">{{ (weeklyBet * weekly.multiplier).toLocaleString() }}</strong> Zoo
            </div>
            <button
              class="btn btn-sm lottery-buy-btn weekly mt-2 w-100"
              :disabled="!weeklyPick || buying === 'weekly'"
              @click="buyTicket('weekly')"
            >
              <span v-if="buying === 'weekly'"><i class="fas fa-spinner fa-spin mr-1"></i>Đang mua...</span>
              <span v-else><i class="fas fa-ticket-alt mr-1"></i>Mua Vé</span>
            </button>
          </div>

          <!-- Already bought notice -->
          <div v-else-if="weekly.status === 'open' && weekly.my_tickets.length && weeklyOpenSeconds === 0" class="lottery-bought-notice weekly">
            <i class="fas fa-check-circle mr-1"></i> Bạn đã mua vé cho giải tuần này.
          </div>

          <!-- My tickets for this draw -->
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

    <!-- ─── Jackpot Card ──────────────────────────────────────────────── -->
    <div class="lottery-jackpot-card">
      <div class="lottery-jackpot-stripe"></div>
      <div class="lottery-jackpot-body">

        <div class="lottery-jackpot-header">
          <span class="lottery-jackpot-badge">🎰 JACKPOT · Trả ngay khi trúng</span>
          <span class="lottery-jackpot-vé">Vé: <strong style="color:#f6c23e;">500 Zoo</strong> / lần · Chọn 2 số 01–99</span>
        </div>

        <!-- Jackpot pot -->
        <div class="lottery-jackpot-pot">
          <div class="lottery-jackpot-pot-label">Tổng giải thưởng hiện tại</div>
          <div class="lottery-jackpot-pot-amount">
            {{ jackpot.total_pot.toLocaleString() }}
            <span style="font-size:1rem;font-weight:400;color:rgba(255,255,255,.5);"> Zoo</span>
          </div>
          <div class="lottery-jackpot-pot-sub">{{ jackpot.total_tickets }} vé đã mua · Tích lũy từ 0 Zoo</div>
        </div>

        <!-- Win flash -->
        <div v-if="jackpotWinMsg" class="lottery-jackpot-win-flash">
          🎉 {{ jackpotWinMsg }}
        </div>

        <!-- Pick form -->
        <div v-if="jackpot.status === 'open' && !jackpotWinMsg" class="lottery-jackpot-form">
          <div class="lottery-jackpot-picks-label">Chọn 2 số khác nhau:</div>
          <div class="lottery-jackpot-pick-row">
            <div>
              <div class="lottery-jackpot-pick-sub">Số thứ nhất</div>
              <div class="lottery-number-grid jp-grid">
                <button
                  v-for="n in 99" :key="'j1-'+n"
                  :class="['lottery-num-btn jackpot', jpPick1 === n ? 'selected' : '', jpPick2 === n ? 'jp-taken' : '']"
                  @click="jpPick1 = n"
                >{{ pad(n) }}</button>
              </div>
            </div>
            <div>
              <div class="lottery-jackpot-pick-sub">Số thứ hai</div>
              <div class="lottery-number-grid jp-grid">
                <button
                  v-for="n in 99" :key="'j2-'+n"
                  :class="['lottery-num-btn jackpot', jpPick2 === n ? 'selected' : '', jpPick1 === n ? 'jp-taken' : '']"
                  @click="jpPick2 = n"
                >{{ pad(n) }}</button>
              </div>
            </div>
          </div>

          <div v-if="jpPick1 && jpPick2" class="text-center mt-2" style="font-size:13px;color:#aaa;">
            Số chọn: <strong style="color:#f6c23e;">{{ pad(jpPick1) }}</strong>
            &amp; <strong style="color:#f6c23e;">{{ pad(jpPick2) }}</strong>
            · Giá: <strong>500 Zoo</strong>
            → Trúng nhận <strong style="color:#f6c23e;">{{ jackpot.total_pot.toLocaleString() }} Zoo</strong>
          </div>

          <button
            class="btn btn-sm lottery-jackpot-buy-btn mt-2 w-100"
            :disabled="!jpPick1 || !jpPick2 || jpPick1 === jpPick2 || buying === 'jackpot'"
            @click="buyJackpot"
          >
            <span v-if="buying === 'jackpot'"><i class="fas fa-spinner fa-spin mr-1"></i>Đang mua...</span>
            <span v-else><i class="fas fa-bolt mr-1"></i>Mua Vé Jackpot · 500 Zoo</span>
          </button>
        </div>

        <!-- My jackpot tickets for this round -->
        <div v-if="jackpot.my_tickets && jackpot.my_tickets.length" class="lottery-my-tickets mt-2">
          <div class="lottery-my-tickets-title">Vé của bạn (ván này):</div>
          <div v-for="t in jackpot.my_tickets" :key="t.id" class="lottery-ticket-row">
            <span>
              <span class="lottery-result-num jackpot">{{ pad((t.picked_numbers||[])[0]) }}</span>
              <span class="lottery-result-num jackpot ml-1">{{ pad((t.picked_numbers||[])[1]) }}</span>
            </span>
            <span class="text-muted ml-2">500 Zoo</span>
            <span v-if="t.is_winner === true"  class="badge badge-warning ml-auto">🎰 Trúng! +{{ (t.payout||0).toLocaleString() }}</span>
            <span v-else-if="t.is_winner === false" class="badge badge-secondary ml-auto">Chưa trúng</span>
            <span v-else class="badge badge-secondary ml-auto">Chờ</span>
          </div>
        </div>

        <!-- Recent jackpot winners -->
        <div v-if="recentJackpot.length" class="mt-3">
          <div class="lottery-my-tickets-title"><i class="fas fa-trophy mr-1" style="color:#f6c23e;"></i> Jackpot gần đây:</div>
          <table class="table table-sm table-dark lottery-table">
            <thead><tr><th>Thời gian</th><th>Số trúng</th><th>Vé</th><th>Giải</th></tr></thead>
            <tbody>
              <tr v-for="d in recentJackpot" :key="d.id">
                <td style="font-size:11px;">{{ d.drawn_at }}</td>
                <td>
                  <span v-for="n in d.winning_numbers" :key="n" class="lottery-result-num jackpot">{{ pad(n) }}</span>
                </td>
                <td>{{ d.total_tickets }}</td>
                <td style="color:#f6c23e;">{{ d.total_pot.toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>

    <!-- My ticket history -->
    <div v-if="myHistory.length" class="lottery-section mb-4">
      <h6 class="lottery-history-title"><i class="fas fa-history mr-1"></i> Lịch sử vé của bạn</h6>
      <table class="table table-sm table-dark lottery-table">
        <thead>
          <tr>
            <th>Loại</th>
            <th>Ngày quay</th>
            <th>Số chọn</th>
            <th>Cược</th>
            <th>Kết quả</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in myHistory" :key="t.id">
            <td>
              <span :class="['badge', t.draw_type === 'jackpot' ? 'badge-warning' : t.draw_type === 'weekly' ? 'badge-purple' : 'badge-green']">
                {{ t.draw_type === 'jackpot' ? '🎰' : t.draw_type === 'weekly' ? 'Tuần' : 'Ngày' }}
              </span>
            </td>
            <td style="font-size:11px;">{{ t.draw_at || '—' }}</td>
            <td>
              <template v-if="t.draw_type === 'jackpot' && t.picked_numbers">
                <span class="lottery-result-num jackpot">{{ pad(t.picked_numbers[0]) }}</span>
                <span class="lottery-result-num jackpot ml-1">{{ pad(t.picked_numbers[1]) }}</span>
              </template>
              <span v-else :class="['lottery-result-num', t.draw_type === 'weekly' ? 'weekly' : '']">
                {{ pad(t.picked_number) }}
              </span>
            </td>
            <td style="font-size:12px;">{{ t.bet_amount.toLocaleString() }}</td>
            <td>
              <span v-if="t.is_winner === true"  class="badge badge-success">Trúng +{{ t.payout.toLocaleString() }}</span>
              <span v-else-if="t.is_winner === false" class="badge badge-danger">Trượt</span>
              <span v-else class="badge badge-secondary">Chờ quay</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Recent results -->
    <div class="lottery-history">
      <div class="lottery-history-col">
        <h6 class="lottery-history-title"><i class="fas fa-list mr-1"></i> Kết quả ngày gần đây</h6>
        <table class="table table-sm table-dark lottery-table">
          <thead><tr><th>Ngày</th><th>Số trúng</th><th>Vé</th><th>Thưởng</th></tr></thead>
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
        <h6 class="lottery-history-title"><i class="fas fa-list mr-1"></i> Kết quả tuần gần đây</h6>
        <table class="table table-sm table-dark lottery-table">
          <thead><tr><th>Tuần</th><th>Số trúng</th><th>Vé</th><th>Thưởng</th></tr></thead>
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
const BET_TIERS = [10, 100, 1000, 10000];

export default {
  name: 'LotteryLobby',
  props: {
    initDaily:          { type: Object, required: true },
    initWeekly:         { type: Object, required: true },
    initJackpot:        { type: Object, required: true },
    initRecentJackpot:  { type: Array,  default: () => [] },
    initRecentDaily:    { type: Array,  default: () => [] },
    initRecentWeekly:   { type: Array,  default: () => [] },
    initBalance:        { type: Number, default: 0 },
    initHistory:             { type: Array,  default: () => [] },
    initLastSettledDaily:    { type: Object, default: null },
    initLastSettledWeekly:   { type: Object, default: null },
    routes:                  { type: Object, required: true },
    csrf:               { type: String, required: true },
  },

  data() {
    return {
      BET_TIERS,

      daily:        { ...this.initDaily,  my_tickets: [...(this.initDaily.my_tickets  || [])] },
      weekly:       { ...this.initWeekly, my_tickets: [...(this.initWeekly.my_tickets || [])] },
      jackpot:      { ...this.initJackpot, my_tickets: [...(this.initJackpot.my_tickets || [])] },
      recentJackpot: [...this.initRecentJackpot],
      recentDaily:  [...this.initRecentDaily],
      recentWeekly: [...this.initRecentWeekly],
      balance:      this.initBalance,
      myHistory:    [...this.initHistory],

      dailySeconds:      this.initDaily.seconds_left       || 0,
      weeklySeconds:     this.initWeekly.seconds_left      || 0,
      dailyOpenSeconds:  this.initDaily.seconds_until_open  || 0,
      weeklyOpenSeconds: this.initWeekly.seconds_until_open || 0,

      dailyPick:  null,
      weeklyPick: null,
      dailyBet:   100,
      weeklyBet:  100,

      jpPick1:       null,
      jpPick2:       null,
      jackpotWinMsg: '',

      lastSettledDaily:  this.initLastSettledDaily  || null,
      lastSettledWeekly: this.initLastSettledWeekly || null,
      claimedDrawIds:    JSON.parse(localStorage.getItem('lottery_claimed') || '[]'),

      buying:  null, // 'daily' | 'weekly' | 'jackpot' | null
      errMsg:  '',

      _ticker:  null,
      _poller:  null,
    };
  },

  mounted() {
    this._ticker = setInterval(() => {
      if (this.dailySeconds > 0)      this.dailySeconds--;
      if (this.weeklySeconds > 0)     this.weeklySeconds--;
      if (this.dailyOpenSeconds > 0)  this.dailyOpenSeconds--;
      if (this.weeklyOpenSeconds > 0) this.weeklyOpenSeconds--;
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

    claimDraw(drawId) {
      if (!this.claimedDrawIds.includes(drawId)) {
        this.claimedDrawIds = [...this.claimedDrawIds, drawId];
        localStorage.setItem('lottery_claimed', JSON.stringify(this.claimedDrawIds));
      }
    },

    fmtOpenTime(isoStr) {
      if (!isoStr) return '';
      const d = new Date(isoStr);
      const h = String(d.getHours()).padStart(2, '0');
      const m = String(d.getMinutes()).padStart(2, '0');
      return `${h}:${m}`;
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
      if (data.recent_jackpot) this.recentJackpot = data.recent_jackpot;
      if (data.my_history) this.myHistory = data.my_history;

      if (data.daily) {
        this.daily             = { ...data.daily, my_tickets: data.daily.my_tickets || [] };
        this.dailySeconds      = data.daily.seconds_left       || 0;
        this.dailyOpenSeconds  = data.daily.seconds_until_open || 0;
      }
      if (data.weekly) {
        this.weekly             = { ...data.weekly, my_tickets: data.weekly.my_tickets || [] };
        this.weeklySeconds      = data.weekly.seconds_left       || 0;
        this.weeklyOpenSeconds  = data.weekly.seconds_until_open || 0;
      }
      if (data.jackpot) {
        this.jackpot = { ...data.jackpot, my_tickets: data.jackpot.my_tickets || [] };
      }
      if (data.last_settled_daily  !== undefined) this.lastSettledDaily  = data.last_settled_daily;
      if (data.last_settled_weekly !== undefined) this.lastSettledWeekly = data.last_settled_weekly;
    },

    async buyJackpot() {
      if (!this.jpPick1 || !this.jpPick2 || this.jpPick1 === this.jpPick2) {
        this.errMsg = 'Chọn 2 số khác nhau.';
        return;
      }
      if (this.balance < 500) {
        this.errMsg = 'Không đủ Zoo. Cần 500 Zoo.';
        return;
      }
      this.buying = 'jackpot';
      try {
        const res = await fetch(this.routes.jackpot, {
          method:  'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-CSRF-TOKEN': this.csrf,
          },
          body: JSON.stringify({ numbers: [this.jpPick1, this.jpPick2] }),
        });
        const data = await res.json();
        if (data.error) { this.errMsg = data.error; return; }

        this.balance = data.balance;
        this.jackpot.total_tickets++;
        this.jackpot.total_pot += 500;

        if (data.won) {
          this.jackpotWinMsg = `🎰 JACKPOT! Bạn trúng ${data.payout.toLocaleString()} Zoo! Số: ${data.numbers.map(n => this.pad(n)).join(' & ')}`;
          // Refresh after 3s to get new jackpot round
          setTimeout(() => {
            this.jackpotWinMsg = '';
            this.jpPick1 = null;
            this.jpPick2 = null;
            this.poll();
          }, 4000);
        } else {
          this.jackpot.my_tickets = [
            { id: Date.now(), picked_numbers: [this.jpPick1, this.jpPick2], bet_amount: 500, is_winner: false, payout: 0 },
            ...this.jackpot.my_tickets,
          ].slice(0, 5);
          this.jpPick1 = null;
          this.jpPick2 = null;
        }
      } catch (_) {
        this.errMsg = 'Lỗi kết nối. Thử lại sau.';
      } finally {
        this.buying = null;
      }
    },

    async buyTicket(type) {
      const pick = type === 'daily' ? this.dailyPick : this.weeklyPick;
      const bet  = type === 'daily' ? this.dailyBet  : this.weeklyBet;
      const draw = type === 'daily' ? this.daily      : this.weekly;

      if (!pick) {
        this.errMsg = 'Vui lòng chọn một số.';
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
          this.daily.my_tickets = [...this.daily.my_tickets, data.ticket];
          this.daily.total_tickets++;
          this.daily.total_pot += bet;
          this.dailyPick = null;
        } else {
          this.weekly.my_tickets = [...this.weekly.my_tickets, data.ticket];
          this.weekly.total_tickets++;
          this.weekly.total_pot += bet;
          this.weeklyPick = null;
        }
        // Refresh history
        this.poll();
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
/* ─── Jackpot Card ───────────────────────────────────────── */
.lottery-jackpot-card {
  position: relative;
  background: linear-gradient(145deg, #1a0a00 0%, #5a2000 50%, #1a0a00 100%);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 40px rgba(255, 140, 0, 0.35);
  margin-bottom: 28px;
}
.lottery-jackpot-stripe {
  height: 3px; width: 100%; position: absolute; top: 0;
  background: linear-gradient(90deg, #b45309, #f59e0b, #fbbf24, #f59e0b, #b45309);
}
.lottery-jackpot-body { padding: 22px 20px 20px; }
.lottery-jackpot-header {
  display: flex; align-items: center; justify-content: space-between;
  flex-wrap: wrap; gap: 6px; margin-bottom: 14px;
}
.lottery-jackpot-badge {
  display: inline-block; padding: 3px 10px; border-radius: 20px;
  font-size: 11px; font-weight: 700; letter-spacing: 1px;
  background: rgba(251,191,36,.2); color: #fbbf24; border: 1px solid #f59e0b55;
}
.lottery-jackpot-vé { font-size: 12px; color: rgba(255,255,255,.5); }

.lottery-jackpot-pot { text-align: center; margin-bottom: 16px; }
.lottery-jackpot-pot-label { font-size: 11px; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: 1px; }
.lottery-jackpot-pot-amount {
  font-size: 3rem; font-weight: 900; color: #fbbf24;
  line-height: 1.1;
  text-shadow: 0 0 24px rgba(251,191,36,.6), 0 2px 8px rgba(0,0,0,.4);
  animation: jpPulse 2s ease-in-out infinite;
}
@keyframes jpPulse {
  0%, 100% { text-shadow: 0 0 24px rgba(251,191,36,.6), 0 2px 8px rgba(0,0,0,.4); }
  50%       { text-shadow: 0 0 40px rgba(251,191,36,.9), 0 2px 16px rgba(0,0,0,.5); }
}
.lottery-jackpot-pot-sub { font-size: 11px; color: rgba(255,255,255,.3); margin-top: 2px; }

.lottery-jackpot-win-flash {
  background: linear-gradient(135deg, #7c3aed, #f59e0b);
  color: #fff; font-size: 1.2rem; font-weight: 900;
  text-align: center; padding: 16px; border-radius: 10px;
  margin-bottom: 12px; animation: winFlash .5s ease-in-out 6;
}
@keyframes winFlash { 0%,100% { opacity:1; } 50% { opacity:.4; } }

.lottery-jackpot-form { }
.lottery-jackpot-picks-label { font-size: 12px; color: rgba(255,255,255,.5); margin-bottom: 8px; }
.lottery-jackpot-pick-row {
  display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
}
.lottery-jackpot-pick-sub { font-size: 11px; color: rgba(255,255,255,.4); margin-bottom: 4px; }
.jp-grid { max-height: 140px; overflow-y: auto; }

.lottery-num-btn.jackpot {
  background: rgba(251,191,36,.1); border: 1px solid rgba(251,191,36,.2); color: #e0e0e0;
}
.lottery-num-btn.jackpot:hover { background: rgba(251,191,36,.25); }
.lottery-num-btn.jackpot.selected { background: #f59e0b; color: #000; border-color: #fbbf24; }
.lottery-num-btn.jp-taken { opacity: .35; pointer-events: none; }

.lottery-jackpot-buy-btn {
  background: linear-gradient(90deg, #b45309, #f59e0b);
  color: #000; font-weight: 800; border: none; font-size: 14px;
  transition: opacity .2s;
}
.lottery-jackpot-buy-btn:hover:not(:disabled) { opacity: .88; }
.lottery-jackpot-buy-btn:disabled { opacity: .4; cursor: not-allowed; }

.lottery-result-num.jackpot {
  background: rgba(251,191,36,.2); color: #fbbf24; border: 1px solid #f59e0b55;
}

/* ─────────────────────────────────────────────────────────── */
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

/* Tier bet buttons */
.lottery-tiers {
  display: flex; gap: 6px;
}
.lottery-tier-btn {
  flex: 1; padding: 5px 0; border-radius: 8px; font-size: 12px; font-weight: 700;
  border: 1px solid rgba(255,255,255,.2);
  background: rgba(255,255,255,.07); color: rgba(255,255,255,.6);
  cursor: pointer; transition: all .15s;
}
.lottery-tier-btn:hover { background: rgba(255,255,255,.18); color: #fff; }
.lottery-tier-btn.selected {
  background: #f6c23e; color: #000; border-color: #f6c23e;
}
.lottery-tier-btn.weekly.selected {
  background: #a855f7; border-color: #a855f7; color: #fff;
}

.lottery-buy-btn { white-space: nowrap; }
.lottery-buy-btn.weekly { background: linear-gradient(90deg,#6c3483,#a855f7); color: #fff; border: none; }

/* Post-draw results banner */
.lottery-result-banner {
  background: rgba(246,194,62,.1); border: 1px solid rgba(246,194,62,.35);
  border-radius: 10px; padding: 12px 14px; margin-bottom: 12px;
}
.lottery-result-banner.weekly {
  background: rgba(168,85,247,.1); border-color: rgba(168,85,247,.35);
}
.lottery-result-banner-title {
  font-size: 11px; font-weight: 700; color: rgba(255,255,255,.5);
  text-transform: uppercase; letter-spacing: .6px; margin-bottom: 8px;
}
.lottery-result-banner-nums { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px; }
.lottery-result-num.lg { width: 32px; height: 32px; font-size: 13px; }
.lottery-winner-row {
  display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
  font-size: 12px; color: #f6c23e; font-weight: 600;
}
.lottery-winner-row.weekly { color: #c084fc; }
.lottery-miss-row {
  display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
  font-size: 12px; color: rgba(255,255,255,.4);
}
.btn-xs { padding: 2px 8px; font-size: 11px; border-radius: 5px; }

/* Not yet open notice */
.lottery-not-open-notice {
  background: rgba(246,194,62,.1); border: 1px solid rgba(246,194,62,.3);
  border-radius: 8px; padding: 8px 14px; margin-bottom: 10px;
  font-size: 12px; color: rgba(246,194,62,.85);
}
.lottery-not-open-notice.weekly {
  background: rgba(168,85,247,.1); border-color: rgba(168,85,247,.3); color: #c084fc;
}

/* Already bought notice */
.lottery-bought-notice {
  background: rgba(46,204,113,.12); border: 1px solid #2ecc7144;
  border-radius: 8px; padding: 10px 14px; margin-bottom: 10px;
  font-size: 13px; color: #2ecc71;
}
.lottery-bought-notice.weekly {
  background: rgba(168,85,247,.12); border-color: #a855f744; color: #c084fc;
}

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

/* Section */
.lottery-section { }

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

.badge-green  { background: rgba(46,204,113,.25); color: #2ecc71; }
.badge-purple { background: rgba(168,85,247,.25); color: #c084fc; }

/* Error toast */
.lottery-error-toast {
  position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
  background: #c0392b; color: #fff; padding: 10px 20px; border-radius: 8px;
  font-size: 13px; cursor: pointer; z-index: 9999;
  box-shadow: 0 4px 16px rgba(0,0,0,.4);
}
</style>
