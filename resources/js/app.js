import './bootstrap';

import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import 'datatables.net';
import 'datatables.net-bs4';
import 'datatables.net-responsive';
import 'datatables.net-responsive-bs4';

// Import Select2 synchronously
import select2 from 'select2';
select2();

import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue';
import CreateEventForm from './components/CreateEventForm.vue';
import InnerWaysCell from './components/InnerWaysCell.vue';
import AddParticipants from './components/AddParticipants.vue';

const app = createApp({});
app.component('create-event-form', CreateEventForm);
app.component('inner-ways-cell', InnerWaysCell);
app.component('add-participants', AddParticipants);
app.mount('#app');
