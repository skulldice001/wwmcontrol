@extends('layouts.admin')

@section('title', __('messages.caro_title'))

@push('styles')
<style>
.caro-wrap    { max-width:900px;margin:0 auto;padding:20px 12px; }
.caro-title   { color:#f0c040;font-size:2rem;font-weight:800;margin-bottom:6px; }
.caro-sub     { color:#aaa;margin-bottom:28px; }
.caro-balance { color:#4ade80;font-weight:700;margin-left:12px; }
.btn-new-caro { background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;
                padding:10px 24px;border-radius:8px;font-weight:700;cursor:pointer;margin-bottom:24px; }
.btn-ai-caro  { background:linear-gradient(135deg,#7c3aed,#5b21b6);color:#fff;border:none;
                padding:10px 24px;border-radius:8px;font-weight:700;cursor:pointer;margin-bottom:24px;margin-left:10px; }
.diff-btns    { display:flex;gap:8px;flex-wrap:wrap;margin-top:6px; }
.diff-btn     { flex:1;padding:8px 0;border-radius:8px;border:2px solid rgba(255,255,255,.15);
                background:rgba(255,255,255,.05);color:#ccc;cursor:pointer;font-weight:700;font-size:.85rem;transition:.15s; }
.diff-btn.active{ border-color:#7c3aed;background:rgba(124,58,237,.25);color:#a78bfa; }
.diff-btn:hover { border-color:#7c3aed;color:#a78bfa; }
.ai-badge     { display:inline-block;background:linear-gradient(90deg,#7c3aed,#5b21b6);color:#fff;
                font-size:.65rem;font-weight:800;padding:1px 6px;border-radius:4px;margin-left:6px;vertical-align:middle; }
.caro-grid    { display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px; }
.caro-card    { background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
                border-radius:12px;padding:18px;transition:transform .15s; }
.caro-card:hover { transform:translateY(-2px); }
.caro-card-name { font-weight:700;color:#e2e8f0;margin-bottom:6px;font-size:1rem; }
.caro-card-meta { color:#aaa;font-size:.82rem;margin-bottom:12px; }
.caro-card-players { color:#ccc;font-size:.85rem;margin-bottom:10px; }
.caro-sym     { display:inline-block;width:22px;height:22px;border-radius:50%;
                text-align:center;line-height:22px;font-weight:800;font-size:.82rem;margin-right:4px; }
.sym-x        { background:#e2e8f0;color:#111; }
.sym-o        { background:#111;color:#e2e8f0;border:2px solid #555; }
.sym-empty    { background:rgba(255,255,255,.08);color:#555; }
.btn-join-caro{ width:100%;padding:8px;background:#3b82f6;color:#fff;border:none;border-radius:8px;
                font-weight:700;cursor:pointer; }
.btn-join-caro:hover { filter:brightness(1.1); }
.btn-join-caro:disabled { background:#555;cursor:not-allowed; }
.caro-empty   { text-align:center;color:#777;padding:60px 20px; }
.fee-tag      { background:#f59e0b;color:#000;padding:2px 8px;border-radius:12px;font-size:.72rem;font-weight:700;margin-left:8px; }

/* Modal */
.modal-overlay{ display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:1000;
                align-items:center;justify-content:center; }
.modal-overlay.open { display:flex; }
.modal-box    { background:#1e1e2e;border:1px solid rgba(255,255,255,.15);border-radius:16px;
                padding:28px 32px;width:min(380px,95vw); }
.modal-title  { color:#f0c040;font-size:1.2rem;font-weight:800;margin-bottom:20px; }
.form-group   { margin-bottom:14px; }
.form-group label { display:block;color:#ccc;font-size:.85rem;margin-bottom:5px; }
.form-group input { width:100%;padding:9px;background:#111;border:1px solid rgba(255,255,255,.15);
                    border-radius:8px;color:#fff;font-size:.9rem;box-sizing:border-box; }
.modal-footer { display:flex;gap:10px;margin-top:18px;justify-content:flex-end; }
.btn-cancel   { padding:8px 16px;background:#333;color:#ccc;border:none;border-radius:8px;cursor:pointer; }
.btn-submit   { padding:8px 20px;background:#10b981;color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer; }
#toast        { position:fixed;bottom:24px;right:24px;background:#1e293b;color:#fff;
                padding:12px 20px;border-radius:10px;display:none;z-index:9999;font-size:.9rem; }
</style>
@endpush

@section('content')
<div class="caro-wrap">
    <div style="display:flex;align-items:baseline;gap:12px;flex-wrap:wrap;">
        <div class="caro-title">{{ __('messages.caro_title') }}</div>
        <span class="caro-balance">{{ number_format(auth()->user()->z_coins) }} Zoo</span>
    </div>
    <div class="caro-sub">{{ __('messages.caro_sub') }}</div>

    <button class="btn-new-caro" onclick="openModal()">{{ __('messages.caro_new_table') }}</button>
    <button class="btn-ai-caro"  onclick="openAiModal()">{{ __('messages.caro_vs_ai') }}</button>

    @if($tables->isEmpty())
        <div class="caro-empty" id="tableContainer">{{ __('messages.caro_empty') }}</div>
    @else
        <div class="caro-grid" id="tableContainer">
            @foreach($tables as $t)
                @php $full = $t->player_x_id && $t->player_o_id; @endphp
                <div class="caro-card" id="caro-table-{{ $t->id }}">
                    <div class="caro-card-name">
                        {{ $t->name }}
                        @if($t->entry_fee > 0)
                            <span class="fee-tag">{{ number_format($t->entry_fee) }} Zoo</span>
                        @endif
                    </div>
                    <div class="caro-card-meta">{{ __('messages.caro_card_meta') }}</div>
                    <div class="caro-card-players">
                        <span class="caro-sym sym-x">X</span>
                        {{ $t->playerX?->name ?? '—' }}
                        &nbsp;&nbsp;
                        <span class="caro-sym sym-o">O</span>
                        {{ $t->playerO?->name ?? '—' }}
                    </div>
                    <button class="btn-join-caro" {{ $full ? 'disabled' : '' }}
                            onclick="joinTable({{ $t->id }}, this)">
                        {{ $full ? __('messages.caro_playing') : __('messages.caro_join') }}
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- AI Modal -->
<div class="modal-overlay" id="aiModal">
    <div class="modal-box">
        <div class="modal-title">{{ __('messages.caro_ai_modal_title') }}</div>
        <div class="form-group">
            <label>{{ __('messages.caro_modal_name_label') }}</label>
            <input id="aiTableName" type="text" maxlength="60" placeholder="{{ __('messages.caro_ai_name_ph') }}">
        </div>
        <div class="form-group">
            <label>{{ __('messages.caro_ai_diff_label') }}</label>
            <div class="diff-btns">
                <button class="diff-btn active" data-diff="easy"   onclick="selectDiff(this)">{{ __('messages.caro_ai_easy') }}</button>
                <button class="diff-btn"        data-diff="medium" onclick="selectDiff(this)">{{ __('messages.caro_ai_medium') }}</button>
                <button class="diff-btn"        data-diff="hard"   onclick="selectDiff(this)">{{ __('messages.caro_ai_hard') }}</button>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeAiModal()">{{ __('messages.caro_modal_cancel') }}</button>
            <button class="btn-submit" style="background:#7c3aed;" onclick="createAiTable()">{{ __('messages.caro_ai_start') }}</button>
        </div>
    </div>
</div>

<!-- PvP Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal-box">
        <div class="modal-title">{{ __('messages.caro_modal_title') }}</div>
        <div class="form-group">
            <label>{{ __('messages.caro_modal_name_label') }}</label>
            <input id="tableName" type="text" maxlength="60" placeholder="{{ __('messages.caro_modal_name_ph') }}">
        </div>
        <div class="form-group">
            <label>{{ __('messages.caro_modal_fee_label') }}</label>
            <input id="entryFee" type="number" min="0" max="100000" value="0">
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">{{ __('messages.caro_modal_cancel') }}</button>
            <button class="btn-submit" onclick="createTable()">{{ __('messages.caro_modal_create') }}</button>
        </div>
    </div>
</div>
<div id="toast"></div>

@push('scripts')
<script>
const CSRF       = document.querySelector('meta[name=csrf-token]').content;
const DIFF_LABEL = {
    easy:   '{{ __("messages.caro_ai_easy") }}',
    medium: '{{ __("messages.caro_ai_medium") }}',
    hard:   '{{ __("messages.caro_ai_hard") }}',
};
const TEXT = {
    defaultName:  '{{ __("messages.caro_default_name") }}',
    defaultAiName:'{{ __("messages.caro_default_ai_name") }}',
    joinLoading:  '{{ __("messages.caro_join_loading") }}',
    joinBtn:      '{{ __("messages.caro_join") }}',
    playing:      '{{ __("messages.caro_playing") }}',
    cardMeta:     '{{ __("messages.caro_card_meta") }}',
    emptySlot:    '—',
    error:        '{{ __("messages.caro_error") }}',
};

function openModal()    { document.getElementById('createModal').classList.add('open'); }
function closeModal()   { document.getElementById('createModal').classList.remove('open'); }
function openAiModal()  { document.getElementById('aiModal').classList.add('open'); }
function closeAiModal() { document.getElementById('aiModal').classList.remove('open'); }

let selectedDiff = 'easy';
function selectDiff(btn) {
    document.querySelectorAll('.diff-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    selectedDiff = btn.dataset.diff;
}

function showToast(msg, ms=3500) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', ms);
}

async function createAiTable() {
    const name = document.getElementById('aiTableName').value || TEXT.defaultAiName;
    const r = await fetch('{{ route("entertainment.caro.create") }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({ name, entry_fee: 0, is_ai_mode: true, ai_difficulty: selectedDiff })
    });
    const d = await r.json();
    if (d.redirect) window.location = d.redirect;
    else showToast(d.error ?? TEXT.error);
}

async function createTable() {
    const r = await fetch('{{ route("entertainment.caro.create") }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({
            name:       document.getElementById('tableName').value || TEXT.defaultName,
            entry_fee:  +document.getElementById('entryFee').value || 0,
        })
    });
    const d = await r.json();
    if (d.redirect) window.location = d.redirect;
    else showToast(d.error ?? TEXT.error);
}

async function joinTable(id, btn) {
    btn.disabled = true; btn.textContent = TEXT.joinLoading;
    const r = await fetch(`/entertainment/caro/${id}/join`, {
        method:'POST', headers:{'X-CSRF-TOKEN':CSRF}
    });
    const d = await r.json();
    if (d.redirect) window.location = d.redirect;
    else { showToast(d.error ?? TEXT.error); btn.disabled = false; btn.textContent = TEXT.joinBtn; }
}

// Real-time lobby updates
@if(function_exists('app') && app()->bound(\Illuminate\Broadcasting\BroadcastManager::class))
window.Echo?.channel('caro-lobby').listen('.table.updated', e => {
    upsertCard(e);
});
@endif

function upsertCard(t) {
    const container = document.getElementById('tableContainer');
    let el = document.getElementById('caro-table-' + t.id);
    const full = t.player_count >= 2;
    const html = `
        <div class="caro-card-name">${esc(t.name)}${t.entry_fee > 0 ? `<span class="fee-tag">${t.entry_fee.toLocaleString()} Zoo</span>` : ''}</div>
        <div class="caro-card-meta">${TEXT.cardMeta}</div>
        <div class="caro-card-players">
            <span class="caro-sym sym-x">X</span>${esc(t.player_x_name ?? TEXT.emptySlot)}
            &nbsp;&nbsp;<span class="caro-sym sym-o">O</span>${esc(t.player_o_name ?? TEXT.emptySlot)}
        </div>
        <button class="btn-join-caro" ${full?'disabled':''} onclick="joinTable(${t.id},this)">${full ? TEXT.playing : TEXT.joinBtn}</button>`;
    if (el) { el.innerHTML = html; }
    else {
        if (container.querySelector('.caro-empty')) {
            container.innerHTML = '';
            container.className = 'caro-grid';
        }
        el = document.createElement('div');
        el.className = 'caro-card'; el.id = 'caro-table-' + t.id;
        el.innerHTML = html;
        container.prepend(el);
    }
}

function esc(s) { return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
</script>
@endpush
@endsection
