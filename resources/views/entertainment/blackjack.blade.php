@extends('layouts.admin')

@section('title', __('messages.blackjack'))

@push('styles')
<style>
.bj-badge-preset {
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    background: rgba(39,174,96,.15);
    color: #2ecc71;
    border: 1px solid rgba(39,174,96,.3);
    border-radius: 10px;
    padding: 1px 7px;
}
</style>
@endpush

@section('content')

@if(session('error'))
<div class="alert alert-danger alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    {{ session('error') }}
</div>
@endif

<div class="row mb-3">
    <div class="col-12 d-flex align-items-center justify-content-between">
        <a href="{{ route('entertainment.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> {{ __('messages.entertainment_hall') }}
        </a>
        <h4 class="mb-0">{{ __('messages.bj_lobby') }}</h4>
        <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#create-bj-modal">
            <i class="fas fa-plus"></i> {{ __('messages.bj_create_table') }}
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped projects mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.table_name') }}</th>
                        <th>{{ __('messages.bj_bet_range') }}</th>
                        <th>{{ __('messages.players') }}</th>
                        <th class="text-center">{{ __('messages.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="bj-table-body">
                @foreach($tables as $table)
                <tr id="bj-row-{{ $table->id }}">
                    <td>
                        <strong>{{ $table->name }}</strong>
                        @if($table->is_preset)
                        <span class="bj-badge-preset ml-1">Official</span>
                        @endif
                        <br><small class="text-muted">Max {{ $table->max_players }} players</small>
                    </td>
                    <td>
                        <span class="badge badge-warning text-dark">
                            {{ number_format($table->min_bet) }} – {{ number_format($table->max_bet) }} Zoo
                        </span>
                    </td>
                    <td class="project_progress">
                        <div class="progress progress-sm">
                            <div class="progress-bar bg-green" role="progressbar"
                                 style="width: {{ $table->max_players > 0 ? ($table->current_players / $table->max_players * 100) : 0 }}%">
                            </div>
                        </div>
                        <small>{{ $table->current_players }} / {{ $table->max_players }}</small>
                    </td>
                    <td class="text-center">
                        @if($table->status === 'waiting')
                            <span class="badge badge-success">{{ __('messages.waiting') }}</span>
                        @elseif($table->status === 'full')
                            <span class="badge badge-danger">{{ __('messages.full') }}</span>
                        @else
                            <span class="badge badge-warning">{{ __('messages.playing') }}</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <button class="btn btn-primary btn-sm join-bj-btn"
                                data-id="{{ $table->id }}"
                                {{ $table->status === 'full' ? 'disabled' : '' }}>
                            <i class="fas fa-sign-in-alt mr-1"></i>{{ __('messages.bj_join') }}
                        </button>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('partials.notify')

{{-- Loading overlay --}}
<div id="bj-loading" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
    <i class="fas fa-spinner fa-spin fa-3x text-white"></i>
</div>

{{-- Create Table Modal --}}
<div class="modal fade" id="create-bj-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:#1a2332;border:1px solid #2d3f55;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">{{ __('messages.bj_create_table') }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ __('messages.table_name') }}</label>
                    <input type="text" id="bj-name" class="form-control" maxlength="60"
                           placeholder="My Table">
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label>{{ __('messages.min_buy_in') }}</label>
                            <input type="number" id="bj-min-bet" class="form-control" min="1" value="100">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label>{{ __('messages.max_buy_in') }}</label>
                            <input type="number" id="bj-max-bet" class="form-control" min="1" value="1000">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>{{ __('messages.max_players') }}</label>
                    <select id="bj-max-players" class="form-control">
                        @foreach(range(2,7) as $n)
                        <option value="{{ $n }}" {{ $n === 5 ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="button" id="create-bj-btn" class="btn btn-success">
                    <i class="fas fa-check mr-1"></i>{{ __('messages.bj_create_table') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content || '';
const CREATE_URL = '{{ route('entertainment.blackjack.create') }}';
const JOIN_BASE  = '{{ url('entertainment/blackjack') }}';

// ── Join ─────────────────────────────────────────────────────────────
document.getElementById('bj-table-body').addEventListener('click', function (e) {
    const btn = e.target.closest('.join-bj-btn');
    if (!btn || btn.disabled) return;
    const id = btn.dataset.id;
    setLoading(true);
    fetch(`${JOIN_BASE}/${id}/join`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
    })
    .then(r => r.json())
    .then(data => {
        if (data.redirect) { window.location = data.redirect; return; }
        if (data.message) { notify('error', data.message); }
    })
    .catch(() => notify('error', 'Connection error.'))
    .finally(() => setLoading(false));
});

// ── Create ───────────────────────────────────────────────────────────
document.addEventListener('click', function (e) {
    const btn = e.target.closest('#create-bj-btn');
    if (!btn || btn.disabled) return;

    const name       = document.getElementById('bj-name').value.trim();
    const minBet     = parseInt(document.getElementById('bj-min-bet').value, 10);
    const maxBet     = parseInt(document.getElementById('bj-max-bet').value, 10);
    const maxPlayers = parseInt(document.getElementById('bj-max-players').value, 10);

    if (!name) { notify('warning', 'Please enter a table name.'); return; }
    if (!minBet || !maxBet || maxBet < minBet) { notify('warning', 'Invalid bet range.'); return; }

    btn.disabled = true;
    fetch(CREATE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ name, min_bet: minBet, max_bet: maxBet, max_players: maxPlayers }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.redirect) { window.location = data.redirect; return; }
        if (data.errors || data.message) {
            const msg = data.message || Object.values(data.errors).flat().join(' ');
            notify('error', msg);
        }
    })
    .catch(() => notify('error', 'Connection error.'))
    .finally(() => { btn.disabled = false; });
});

// ── WebSocket: live lobby updates ────────────────────────────────────
function updateRow(t) {
    const row = document.getElementById('bj-row-' + t.id);
    if (!row) {
        // New table added — reload page to show new row
        window.location.reload();
        return;
    }
    // Update player count
    const prog = row.querySelector('.progress-bar');
    const cnt  = row.querySelector('.project_progress small');
    if (prog) prog.style.width = (t.max_players > 0 ? (t.current_players / t.max_players * 100) : 0) + '%';
    if (cnt)  cnt.textContent  = t.current_players + ' / ' + t.max_players;

    // Update status badge
    const statusCell = row.querySelector('.project-state, td:nth-child(4)');
    // Update join button
    const joinBtn = row.querySelector('.join-bj-btn');
    if (t.status === 'closed') {
        row.remove();
        return;
    }
    if (joinBtn) joinBtn.disabled = t.status === 'full';
}

if (window.initEcho) window.initEcho();
if (window.Echo) {
    window.Echo.channel('blackjack.lobby')
        .listen('BlackjackTableUpdated', e => {
            if (e.table) updateRow(e.table);
        });
}

function setLoading(on) {
    document.getElementById('bj-loading').style.display = on ? 'flex' : 'none';
}
</script>
@endpush
