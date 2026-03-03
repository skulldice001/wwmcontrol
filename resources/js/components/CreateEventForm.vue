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
            </select>
            <!-- Hidden input for type if it's disabled to ensure it sends -->
            <input v-if="initialType !== ''" type="hidden" name="type" :value="form.type">
            <span v-if="errors.type" class="error invalid-feedback">{{ errors.type }}</span>
        </div>

        <div class="form-group">
            <label>{{ translations.start_time_label }}</label>
            <input type="datetime-local" name="start_time" class="form-control"
                   :class="{'is-invalid': errors.start_time}"
                   v-model="form.start_time"
                   :readonly="isGuildWar">
            <span v-if="errors.start_time" class="error invalid-feedback">{{ errors.start_time }}</span>
        </div>

        <div class="form-group">
            <label>{{ translations.end_time_label }}</label>
            <input type="datetime-local" name="end_time" class="form-control"
                   :class="{'is-invalid': errors.end_time}"
                   v-model="form.end_time"
                   :readonly="isGuildWar">
            <span v-if="errors.end_time" class="error invalid-feedback">{{ errors.end_time }}</span>
        </div>

        <div class="form-group" v-if="!isGuildWar">
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
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary">{{ translations.create_btn }}</button>
        <a :href="routeIndex" class="btn btn-default float-right">{{ translations.cancel_btn }}</a>
    </div>
  </form>
</template>

<script>
import { ref, onMounted, computed } from 'vue';

export default {
    props: {
        csrfToken: String,
        routeStore: String,
        routeIndex: String,
        oldInput: {
            type: Object,
            default: () => ({})
        },
        errors: {
            type: Object,
            default: () => ({})
        },
        initialType: {
            type: String,
            default: ''
        },
        translations: {
            type: Object,
            default: () => ({})
        }
    },
    setup(props) {
        const form = ref({
            discord_id: props.oldInput.discord_id || '',
            title: props.oldInput.title || '',
            description: props.oldInput.description || '',
            type: props.initialType || props.oldInput.type || 'casual',
            start_time: props.oldInput.start_time || '',
            end_time: props.oldInput.end_time || '',
            location: props.oldInput.location || '',
            status: props.oldInput.status || 'upcoming',
        });

        const isGuildWar = computed(() => form.value.type === 'guild_war');

        const calculateGuildWarTimes = () => {
            const now = new Date();
            let nextSat = new Date();

            const day = now.getDay();
            const hour = now.getHours();
            const minute = now.getMinutes();

            let addDays = 0;
            if (day === 6) { // Saturday
                if (hour < 19 || (hour === 19 && minute < 30)) {
                    addDays = 0;
                } else {
                    addDays = 7;
                }
            } else {
                addDays = (6 - day + 7) % 7;
            }

            nextSat.setDate(now.getDate() + addDays);

            const startTime = new Date(nextSat);
            startTime.setHours(19, 30, 0, 0);

            const endTime = new Date(startTime);
            endTime.setDate(startTime.getDate() + 1);
            endTime.setHours(22, 30, 0, 0);

            const formatDateTime = (date) => {
                const pad = (num) => String(num).padStart(2, '0');
                return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
            };

            form.value.start_time = formatDateTime(startTime);
            form.value.end_time = formatDateTime(endTime);

            const satDate = startTime.getDate();
            const sunDate = endTime.getDate();
            const month = startTime.getMonth() + 1;
            const endMonth = endTime.getMonth() + 1;

            let title = '';
            if (month !== endMonth) {
                 title = props.translations.guild_war_title_format
                    .replace(':startDate', satDate)
                    .replace(':startMonth', month)
                    .replace(':endDate', sunDate)
                    .replace(':endMonth', endMonth);
            } else {
                 title = props.translations.guild_war_title_format_same_month
                    .replace(':startDate', satDate)
                    .replace(':endDate', sunDate)
                    .replace(':month', month);
            }

            form.value.title = title;
        };

        const handleTypeChange = () => {
            if (form.value.type === 'guild_war') {
                calculateGuildWarTimes();
            }
        };

        onMounted(() => {
            if (form.value.type === 'guild_war') {
                calculateGuildWarTimes();
            }
        });

        return {
            form,
            isGuildWar,
            handleTypeChange
        };
    }
}
</script>
