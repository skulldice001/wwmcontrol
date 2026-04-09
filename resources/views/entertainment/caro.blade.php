@extends('layouts.admin')

@section('title', 'Cờ Caro')

@push('styles')
<style>
.caro-wrap    { max-width:900px;margin:0 auto;padding:20px 12px; }
.caro-title   { color:#f0c040;font-size:2rem;font-weight:800;margin-bottom:6px; }
.caro-sub     { color:#aaa;margin-bottom:28px; }
.caro-balance { color:#4ade80;font-weight:700;margin-left:12px; }
.btn-new-caro { background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;
                padding:10px 24px;border-radius:8px;font-weight:700;cursor:pointer;margin-bottom:24px; }
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
        <div class="caro-title">⊞ Cờ Caro</div>
        <span class="caro-balance">{{ number_format(auth()->user()->z_coins) }} Zoo</span>
    </div>
    <div class="caro-sub">Đặt 5 quân liên tiếp để thắng. Bàn cờ tự mở rộng khi cần.</div>

    <button class="btn-new-caro" onclick="openModal()">+ Tạo bàn mới</button>

    @if($tables->isEmpty())
        <div class="caro-empty" id="tableContainer">Chưa có bàn nào. Hãy tạo bàn để bắt đầu!</div>
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
                    <div class="caro-card-meta">Bàn cờ tự mở rộng · 5 quân liên tiếp</div>
                    <div class="caro-card-players">
                        <span class="caro-sym sym-x">X</span>
                        {{ $t->playerX?->name ?? '—' }}
                        &nbsp;&nbsp;
                        <span class="caro-sym sym-o">O</span>
                        {{ $t->playerO?->name ?? '—' }}
                    </div>
                    <button class="btn-join-caro" {{ $full ? 'disabled' : '' }}
                            onclick="joinTable({{ $t->id }}, this)">
                        {{ $full ? 'Đang chơi' : 'Vào bàn' }}
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Modal -->
<div class="modal-overlay" id="createModal">
    <div class="modal-box">
        <div class="modal-title">Tạo bàn cờ caro</div>
        <div class="form-group">
            <label>Tên bàn</label>
            <input id="tableName" type="text" maxlength="60" placeholder="VD: Bàn của tôi">
        </div>
        <div class="form-group">
            <label>Phí vào bàn (Zoo, 0 = miễn phí)</label>
            <input id="entryFee" type="number" min="0" max="100000" value="0">
        </div>
        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Hủy</button>
            <button class="btn-submit" onclick="createTable()">Tạo</button>
        </div>
    </div>
</div>
<div id="toast"></div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;

function openModal()  { document.getElementById('createModal').classList.add('open'); }
function closeModal() { document.getElementById('createModal').classList.remove('open'); }

function showToast(msg, ms=3500) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', ms);
}

async function createTable() {
    const r = await fetch('{{ route("entertainment.caro.create") }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({
            name:       document.getElementById('tableName').value || 'Bàn Caro',
            entry_fee:  +document.getElementById('entryFee').value || 0,
        })
    });
    const d = await r.json();
    if (d.redirect) window.location = d.redirect;
    else showToast(d.error ?? 'Lỗi');
}

async function joinTable(id, btn) {
    btn.disabled = true; btn.textContent = '...';
    const r = await fetch(`/entertainment/caro/${id}/join`, {
        method:'POST', headers:{'X-CSRF-TOKEN':CSRF}
    });
    const d = await r.json();
    if (d.redirect) window.location = d.redirect;
    else { showToast(d.error ?? 'Lỗi'); btn.disabled = false; btn.textContent = 'Vào bàn'; }
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
        <div class="caro-card-meta">Bàn cờ tự mở rộng · 5 quân liên tiếp</div>
        <div class="caro-card-players">
            <span class="caro-sym sym-x">X</span>${esc(t.player_x_name ?? '—')}
            &nbsp;&nbsp;<span class="caro-sym sym-o">O</span>${esc(t.player_o_name ?? '—')}
        </div>
        <button class="btn-join-caro" ${full?'disabled':''} onclick="joinTable(${t.id},this)">${full?'Đang chơi':'Vào bàn'}</button>`;
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
