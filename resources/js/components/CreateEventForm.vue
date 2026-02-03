<template>
  <form :action="routeStore" method="POST">
    <input type="hidden" name="_token" :value="csrfToken">
    
    <div class="card-body">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="form-control" 
                   :class="{'is-invalid': errors.title}" 
                   v-model="form.title" 
                   :readonly="isGuildWar"
                   placeholder="Enter event title">
            <span v-if="errors.title" class="error invalid-feedback">{{ errors.title }}</span>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3" v-model="form.description" placeholder="Enter description"></textarea>
        </div>

        <div class="form-group">
            <label>Type</label>
            <select name="type" class="form-control" 
                    :class="{'is-invalid': errors.type}" 
                    v-model="form.type" 
                    :disabled="initialType !== ''"
                    @change="handleTypeChange"
                    :style="initialType !== '' ? 'pointer-events:none;background-color:#e9ecef;' : ''">
                <option value="casual">Casual</option>
                <option value="guild_war">Guild War</option>
            </select>
            <!-- Hidden input for type if it's disabled to ensure it sends -->
            <input v-if="initialType !== ''" type="hidden" name="type" :value="form.type">
            <span v-if="errors.type" class="error invalid-feedback">{{ errors.type }}</span>
        </div>

        <div class="form-group">
            <label>Start Time</label>
            <input type="datetime-local" name="start_time" class="form-control" 
                   :class="{'is-invalid': errors.start_time}" 
                   v-model="form.start_time"
                   :readonly="isGuildWar">
            <span v-if="errors.start_time" class="error invalid-feedback">{{ errors.start_time }}</span>
        </div>

        <div class="form-group">
            <label>End Time</label>
            <input type="datetime-local" name="end_time" class="form-control" 
                   :class="{'is-invalid': errors.end_time}" 
                   v-model="form.end_time"
                   :readonly="isGuildWar">
            <span v-if="errors.end_time" class="error invalid-feedback">{{ errors.end_time }}</span>
        </div>

        <div class="form-group" v-if="!isGuildWar">
            <label>Location</label>
            <input type="text" name="location" class="form-control" v-model="form.location" placeholder="Enter location">
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" v-model="form.status">
                <option value="upcoming">Upcoming</option>
                <option value="ongoing">Ongoing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary">Create Event</button>
        <a :href="routeIndex" class="btn btn-default float-right">Cancel</a>
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
        }
    },
    setup(props) {
        const form = ref({
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
                 title = `Guild war ngày ${satDate}/${month} - ${sunDate}/${endMonth}`;
            } else {
                 title = `Guild war ngày ${satDate} - ${sunDate} tháng ${month}`;
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
