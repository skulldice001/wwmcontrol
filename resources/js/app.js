import './bootstrap';

// jQuery is already imported and assigned to window in bootstrap.js
// but we import it here again to ensure it's available for the following imports
import jQuery from 'jquery';

import DataTable from 'datatables.net-bs4';
import 'datatables.net-responsive-bs4';

// Fix for DataTables in production build:
// Explicitly attach DataTable to the global jQuery instance if it's not there.
// This handles cases where the module system might isolate the jQuery instance.
if (window.jQuery && !window.jQuery.fn.DataTable) {
    window.jQuery.fn.DataTable = DataTable;
}
// Also expose DataTable globally just in case
window.DataTable = DataTable;

import select2 from 'select2';
select2();

import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import { createApp } from 'vue';
import CreateEventForm from './components/CreateEventForm.vue';
import InnerWaysCell from './components/InnerWaysCell.vue';
import AddParticipants from './components/AddParticipants.vue';
import TeamMemberSelector from './components/TeamMemberSelector.vue';
import BlackjackRoom from './components/BlackjackRoom.vue';
import BlackjackLobby from './components/BlackjackLobby.vue';
import PokerRoom from './components/PokerRoom.vue';
import TaixiuLobby from './components/TaixiuLobby.vue';
import TaixiuRoom from './components/TaixiuRoom.vue';
import LotteryLobby from './components/LotteryLobby.vue';

const app = createApp({});
app.config.globalProperties.window = window;
app.component('create-event-form', CreateEventForm);
app.component('inner-ways-cell', InnerWaysCell);
app.component('add-participants', AddParticipants);
app.component('team-member-selector', TeamMemberSelector);
app.component('blackjack-room', BlackjackRoom);
app.component('blackjack-lobby', BlackjackLobby);
app.component('poker-room', PokerRoom);
app.component('taixiu-lobby', TaixiuLobby);
app.component('taixiu-room', TaixiuRoom);
app.component('lottery-lobby', LotteryLobby);
app.mount('#app');
