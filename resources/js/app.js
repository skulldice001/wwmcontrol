import './bootstrap';

import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import 'datatables.net';
import 'datatables.net-bs4';
import 'datatables.net-responsive';
import 'datatables.net-responsive-bs4';

// Import Select2 synchronously
import select2 from 'select2/dist/js/select2.full.min.js';
select2(window.jQuery);

import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue';
import CreateEventForm from './components/CreateEventForm.vue';
import InnerWaysCell from './components/InnerWaysCell.vue';

const app = createApp({});
app.component('create-event-form', CreateEventForm);
app.component('inner-ways-cell', InnerWaysCell);
app.mount('#app');
