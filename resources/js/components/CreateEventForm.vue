<template>
  <form :action="routeStore" method="POST">
    <input type="hidden" name="_token" :value="csrfToken">

    <div class="card-body">
        <div class="form-group">
            <label>{{ translations.title_label }}</label>
            <input type="text" name="title" class="form-control"
                   :class="{'is-invalid': errors.title}"
                   v-model="form.title"
                   :readonly="isGuildWar"
                   :placeholder="translations.title_placeholder">
            <span v-if="errors.title" class="error invalid-feedback">{{ errors.title }}</span>
        </div>

        <div class="form-group">
            <label>{{ translations.description_label }}</label>
            <textarea name="description" class="form-control" rows="3" v-model="form.description" :placeholder="translations.description_placeholder"></textarea>
        </div>

        <div class="form-group">
            <label>{{ translations.type_label }}</label>
            <select name="type" class="form-control"
                    :class="{'is-invalid': errors.type}"
                    v-model="form.type"
                    :disabled="initialType !== ''"
                    @change="handleTypeChange"
                    :style="initialType !== '' ? 'pointer-events:none;background-color:#e9ecef;' : ''">
                <option value="casual">{{ translations.type_casual }}</option>
                <option value="guild_war">{{ translations.type_guild_war }}</option>
                <option value="lucky_draw">🎲 {{ translations.type_lucky_draw || 'Quay Số Ngẫu Nhiên' }}</option>
            </select>
            <input v-if="initialType !== ''" type="hidden" name="type" :value="form.type">
            <span v-if="errors.type" class="error invalid-feedback">{{ errors.type }}</span>
        </div>

        <div class="form-group">
            <label>{{ isLuckyDraw ? 'Thời gian đăng ký (hạn chót)' : translations.start_time_label }}</label>
            <input type="datetime-local" name="start_time" class="form-control"
                   :class="{'is-invalid': errors.start_time}"
                   v-model="form.start_time"
                   :readonly="isGuildWar">
            <small v-if="isLuckyDraw" class="text-muted">Sau thời gian này người chơi không thể đăng ký tham gia.</small>
            <span v-if="errors.start_time" class="error invalid-feedback">{{ errors.start_time }}</span>
        </div>

        <div class="form-group">
            <label>{{ isLuckyDraw ? '⏰ Thời gian quay số' : translations.end_time_label }}</label>
            <input type="datetime-local" name="end_time" class="form-control"
                   :class="{'is-invalid': errors.end_time}"
                   v-model="form.end_time"
                   :required="isLuckyDraw"
                   :readonly="isGuildWar">
            <small v-if="isLuckyDraw" class="text-muted">Hệ thống tự quay khi đến giờ. Staff cũng có thể quay thủ công.</small>
            <span v-if="errors.end_time" class="error invalid-feedback">{{ errors.end_time }}</span>
        </div>

        <div class="form-group" v-if="!isGuildWar && !isLuckyDraw">
            <label>{{ translations.location_label }}</label>
            <input type="text" name="location" class="form-control" v-model="form.location" :placeholder="translations.location_placeholder">
        </div>

        <div class="form-group">
            <label>{{ translations.status_label }}</label>
            <select name="status" class="form-control" v-model="form.status">
                <option value="upcoming">{{ translations.status_upcoming }}</option>
                <option value="ongoing">{{ translations.status_ongoing }}</option>
                <option value="completed">{{ translations.status_completed }}</option>
                <option value="cancelled">{{ translations.status_cancelled }}</option>
            </select>
        </div>

        <!-- ── Lucky Draw section ───────────────────────────────────── -->
        <template v-if="isLuckyDraw">
            <hr>
            <div class="d-flex align-items-center mb-2">
                <h6 class="mb-0">🎁 Danh sách giải thưởng</h6>
                <button type="button" class="btn btn-sm btn-success ml-3" @click="addPrize">
                    <i class="fas fa-plus"></i> Thêm giải
                </button>
            </div>
            <small class="text-muted d-block mb-3">
                Mỗi giải quay 1 người trúng, không trùng nhau. Thứ tự từ trên xuống = thứ hạng giải.
            </small>

            <div v-for="(prize, i) in prizes" :key="i"
                 class="card card-outline card-secondary mb-2">
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="badge badge-warning">Giải {{ i + 1 }}</span>
                        </div>
                        <div class="col">
                            <input type="text"
                                   :name="`prizes[${i}][name]`"
                                   class="form-control form-control-sm"
                                   placeholder="Tên giải (VD: Giải nhất)"
                                   v-model="prize.name" required>
                        </div>
                        <div class="col">
                            <input type="text"
                                   :name="`prizes[${i}][description]`"
                                   class="form-control form-control-sm"
                                   placeholder="Mô tả phần thưởng"
                                   v-model="prize.description">
                        </div>
                        <div class="col-3">
                            <div class="input-group input-group-sm">
                                <input type="number"
                                       :name="`prizes[${i}][zoo_coin_amount]`"
                                       class="form-control form-control-sm"
                                       placeholder="Zoo Coins" min="0"
                                       v-model.number="prize.zoo_coin_amount">
                                <div class="input-group-append">
                                    <span class="input-group-text">🪙</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-danger btn-sm"
                                    v-if="prizes.length > 1"
                                    @click="removePrize(i)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <!-- ── End Lucky Draw ──────────────────────────────────────── -->

    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary">{{ translations.create_btn }}</button>
        <a :href="routeIndex" class="btn btn-default float-right">{{ translations.cancel_btn }}</a>
    </div>
  </form>
</template>

<script>
import { ref, computed, onMounted } from 'vue';

export default {
    props: {
        csrfToken: String,
        routeStore: String,
        routeIndex: String,
        oldInput: { type: Object, default: () => ({}) },
        errors:   { type: Object, default: () => ({}) },
        initialType: { type: String, default: '' },
        translations: { type: Object, default: () => ({}) },
        users: { type: Array, default: () => [] },
    },
    setup(props) {
        const form = ref({
            discord_id:  props.oldInput.discord_id  || '',
            title:       props.oldInput.title       || '',
            description: props.oldInput.description || '',
            type:        props.initialType || props.oldInput.type || 'casual',
            start_time:  props.oldInput.start_time  || '',
            end_time:    props.oldInput.end_time     || '',
            location:    props.oldInput.location    || '',
            status:      props.oldInput.status      || 'upcoming',
            draw_at:     props.oldInput.draw_at     || '',
        });

        // Restore old prizes on validation error
        const oldPrizes = props.oldInput.prizes;
        const prizes = ref(
            Array.isArray(oldPrizes) && oldPrizes.length
                ? oldPrizes.map(p => ({ name: p.name || '', description: p.description || '', zoo_coin_amount: p.zoo_coin_amount || 0 }))
                : [{ name: '', description: '', zoo_coin_amount: 0 }]
        );

        const isGuildWar  = computed(() => form.value.type === 'guild_war');
        const isLuckyDraw = computed(() => form.value.type === 'lucky_draw');

        const addPrize    = () => prizes.value.push({ name: '', description: '', zoo_coin_amount: 0 });
        const removePrize = (i) => prizes.value.splice(i, 1);

        const calculateGuildWarTimes = () => {
            const now = new Date();
            let nextSat = new Date();
            const day = now.getDay(), hour = now.getHours(), minute = now.getMinutes();
            let addDays = 0;
            if (day === 6) {
                addDays = (hour < 19 || (hour === 19 && minute < 30)) ? 0 : 7;
            } else {
                addDays = (6 - day + 7) % 7;
            }
            nextSat.setDate(now.getDate() + addDays);
            const startTime = new Date(nextSat);
            startTime.setHours(19, 30, 0, 0);
            const endTime = new Date(startTime);
            endTime.setDate(startTime.getDate() + 1);
            endTime.setHours(22, 30, 0, 0);
            const pad = n => String(n).padStart(2, '0');
            const fmt = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            form.value.start_time = fmt(startTime);
            form.value.end_time   = fmt(endTime);
            const satDate = startTime.getDate(), sunDate = endTime.getDate();
            const month = startTime.getMonth() + 1, endMonth = endTime.getMonth() + 1;
            form.value.title = month !== endMonth
                ? (props.translations.guild_war_title_format || '')
                    .replace(':startDate', satDate).replace(':startMonth', month)
                    .replace(':endDate', sunDate).replace(':endMonth', endMonth)
                : (props.translations.guild_war_title_format_same_month || '')
                    .replace(':startDate', satDate).replace(':endDate', sunDate).replace(':month', month);
        };

        const handleTypeChange = () => {
            if (isGuildWar.value) calculateGuildWarTimes();
        };

        onMounted(() => {
            if (isGuildWar.value) calculateGuildWarTimes();
        });

        return { form, prizes, isGuildWar, isLuckyDraw, addPrize, removePrize, handleTypeChange };
    }
}
</script>
