import './bootstrap';

import jQuery from 'jquery';
import select2 from 'select2/dist/js/select2.full.min.js';
select2(jQuery);

window.$ = window.jQuery = jQuery;

import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue';
import CreateEventForm from './components/CreateEventForm.vue';
import InnerWaysCell from './components/InnerWaysCell.vue';

const app = createApp({});
app.component('create-event-form', CreateEventForm);
app.component('inner-ways-cell', InnerWaysCell);
app.mount('#app');
