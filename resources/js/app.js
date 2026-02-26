import './bootstrap';

import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import 'datatables.net';
import 'datatables.net-bs4';
import 'datatables.net-responsive';
import 'datatables.net-responsive-bs4';

// Import plugins dynamically to ensure jQuery is available globally
import('select2/dist/js/select2.full.min.js').then((select2) => {
    select2.default(jQuery);
});

import('bootstrap/dist/js/bootstrap.bundle.min.js');

// Import AdminLTE dynamically to ensure window.$ is set before it runs
import('admin-lte/dist/js/adminlte.min.js');

import { createApp } from 'vue';
import CreateEventForm from './components/CreateEventForm.vue';
import InnerWaysCell from './components/InnerWaysCell.vue';

const app = createApp({});
app.component('create-event-form', CreateEventForm);
app.component('inner-ways-cell', InnerWaysCell);
app.mount('#app');
