@extends('layouts.admin')

@section('title', 'Cờ Caro – ' . $table->name)

@push('styles')
<style>
/* ── Force dark background for the entire content area ── */
.content-wrapper { background: #0f172a !important; }
.content-header  { background: #0f172a !important; }

/* ── Layout ── */
.room-wrap   { display:flex;gap:16px;max-width:1180px;margin:0 auto;padding:16px 12px;flex-wrap:wrap; }
.room-main   { flex:1;min-width:0; }
.room-side   { width:240px;flex-shrink:0;display:flex;flex-direction:column;gap:12px; }

/* ── Header ── */
.room-header { display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px; }
.room-title  { color:#f0c040;font-size:1.3rem;font-weight:800; }
.player-tag  { display:flex;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;font-size:.85rem;font-weight:700; }
.tag-x       { background:rgba(226,232,240,.12);border:2px solid rgba(226,232,240,.35);color:#e2e8f0; }
.tag-o       { background:rgba(255,255,255,.07);border:2px solid rgba(150,150,150,.4);color:#ccc; }
.sym-circle  { width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:900; }
.sym-x-fill  { background:#e2e8f0;color:#111; }
.sym-o-fill  { background:#333;color:#eee;border:2px solid #666; }
.btn-leave   { margin-left:auto;padding:6px 14px;background:#ef4444;color:#fff;border:none;
               border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:600; }
.ai-badge    { background:linear-gradient(90deg,#7c3aed,#5b21b6);color:#fff;font-size:.65rem;
               font-weight:800;padding:1px 6px;border-radius:4px;margin-left:4px; }
.thinking    { color:#a78bfa;font-style:italic; }
@keyframes dots { 0%,20%{content:'.'}40%,60%{content:'..'}80%,100%{content:'...'} }
.thinking::after { content:'.'; animation:dots 1.2s steps(1,end) infinite; }

/* ── Status bar ── */
.status-bar  { background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);
               border-radius:10px;padding:10px 16px;
               margin-bottom:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap; }
.status-text { font-size:.9rem;color:#e2e8f0;flex:1; }
.timer-bar   { width:180px;height:5px;background:rgba(255,255,255,.12);border-radius:3px;overflow:hidden; }
.timer-fill  { height:100%;border-radius:3px;background:#10b981;transition:width .5s linear,background .3s; }
.timer-label { font-size:.8rem;color:#94a3b8;min-width:30px;text-align:right; }

/* ── Canvas board ── */
.board-wrapper { position:relative;overflow:hidden;border-radius:12px;
                 background:#f5deb3;cursor:crosshair;user-select:none;
                 touch-action:none;border:2px solid #c8a96e; }
#caroCanvas    { display:block; }

/* ── Result overlay ── */
.result-overlay { position:absolute;inset:0;background:rgba(0,0,0,.75);
                  display:flex;flex-direction:column;align-items:center;justify-content:center;
                  border-radius:12px;z-index:10; }
.result-title  { color:#f0c040;font-size:2rem;font-weight:900;margin-bottom:12px;text-shadow:0 2px 8px #000; }
.result-sub    { color:#cbd5e1;margin-bottom:20px;font-size:1rem; }
.btn-rematch   { padding:10px 28px;background:#10b981;color:#fff;border:none;border-radius:10px;
                 cursor:pointer;font-weight:700;font-size:1rem;margin-right:8px; }
.btn-leave-res { padding:10px 22px;background:#ef4444;color:#fff;border:none;border-radius:10px;
                 cursor:pointer;font-weight:700;font-size:1rem; }

/* ── Side ── */
.info-card   { background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:14px; }
.info-label  { color:#94a3b8;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px; }
.info-value  { color:#f0c040;font-size:1.15rem;font-weight:800; }
.move-log    { background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:12px;flex:1;overflow:hidden; }
.move-log-title { color:#94a3b8;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px; }
.move-list   { max-height:320px;overflow-y:auto;font-size:.8rem;color:#cbd5e1; }
.move-item   { padding:3px 0;border-bottom:1px solid rgba(255,255,255,.06); }
.move-item .sym{ font-weight:900; }

#toast { position:fixed;bottom:24px;right:24px;background:#1e293b;color:#fff;
         padding:12px 20px;border-radius:10px;display:none;z-index:9999;font-size:.9rem;
         border:1px solid rgba(255,255,255,.1); }
</style>
@endpush

@section('content')
<div class="room-wrap">
  <!-- Main -->
  <div class="room-main">
    <div class="room-header">
        <div class="room-title">⊞ {{ $table->name }}</div>
        <div class="player-tag tag-x" id="tagX">
            <span class="sym-circle sym-x-fill">X</span>
            <span id="nameX">{{ $table->playerX?->name ?? '?' }}</span>
        </div>
        <span style="color:#555">{{ __('messages.caro_room_vs') }}</span>
        <div class="player-tag tag-o" id="tagO">
            <span class="sym-circle sym-o-fill">O</span>
            <span id="nameO">
                @if($table->is_ai_mode)
                    AI <span class="ai-badge">{{ match($table->ai_difficulty) { 'easy' => __('messages.caro_diff_easy'), 'hard' => __('messages.caro_diff_hard'), default => __('messages.caro_diff_medium') } }}</span>
                @else
                    {{ $table->playerO?->name ?? '?' }}
                @endif
            </span>
        </div>
        <button class="btn-leave" onclick="leaveTable()">{{ __('messages.caro_room_leave') }}</button>
    </div>

    <div class="status-bar">
        <div class="status-text" id="statusText"></div>
        <div class="timer-bar"><div class="timer-fill" id="timerFill" style="width:100%"></div></div>
        <div class="timer-label" id="timerLabel">—</div>
    </div>

    <div class="board-wrapper" id="boardWrapper">
        <canvas id="caroCanvas"></canvas>
        <div class="result-overlay" id="resultOverlay" style="display:none;">
            <div class="result-title" id="resultTitle">—</div>
            <div class="result-sub"   id="resultSub">—</div>
            <div>
                <button class="btn-rematch"   onclick="rematch()">{{ __('messages.caro_room_rematch') }}</button>
                <button class="btn-leave-res" onclick="leaveTable()">{{ __('messages.caro_room_leave_btn') }}</button>
            </div>
        </div>
    </div>
  </div>

  <!-- Side -->
  <div class="room-side">
    <div class="info-card">
        <div class="info-label">{{ __('messages.caro_room_balance') }}</div>
        <div class="info-value" id="myBalance">{{ number_format(auth()->user()->z_coins) }} Zoo</div>
    </div>
    @if($table->entry_fee > 0)
    <div class="info-card">
        <div class="info-label">{{ __('messages.caro_room_entry_fee') }}</div>
        <div class="info-value">{{ number_format($table->entry_fee) }} Zoo</div>
    </div>
    @endif
    <div class="move-log">
        <div class="move-log-title">{{ __('messages.caro_room_move_log') }}</div>
        <div class="move-list" id="moveList"></div>
    </div>
  </div>
</div>

<div id="toast"></div>

@push('scripts')
<script>
// ── Constants ────────────────────────────────────────────────────────────────
const TABLE_ID   = {{ $table->id }};
const MY_SYM     = '{{ $mySymbol }}';
const IS_AI      = {{ $table->is_ai_mode ? 'true' : 'false' }};
const AI_DIFF    = '{{ $table->ai_difficulty ?? 'medium' }}';
const DIFF_LABEL = {
    easy:   '{{ __("messages.caro_diff_easy") }}',
    medium: '{{ __("messages.caro_diff_medium") }}',
    hard:   '{{ __("messages.caro_diff_hard") }}',
};
const CSRF       = document.querySelector('meta[name=csrf-token]').content;
const CELL       = 34;   // px per cell (grid spacing)
const STONE_R    = 14;   // stone radius
const TURN_MS    = {{ \App\Services\CaroEngine::TURN_SECONDS }} * 1000;
const TEXT = {
    loading:    '{{ __("messages.caro_room_loading") }}',
    myTurn:     () => MY_SYM.trim() === 'X' ? '{{ __("messages.caro_room_my_turn_x") }}' : '{{ __("messages.caro_room_my_turn_o") }}',
    aiThinking: () => '{{ __("messages.caro_room_ai_thinking") }}'.replace(':diff', DIFF_LABEL[AI_DIFF] ?? '{{ __("messages.caro_diff_medium") }}'),
    waitOpp:    name => '{{ __("messages.caro_room_wait_opp") }}'.replace(':name', name ?? '...'),
    waitLobby:  '{{ __("messages.caro_room_wait_lobby") }}',
    winTitle:   '{{ __("messages.caro_room_win") }}',
    loseTitle:  '{{ __("messages.caro_room_lose") }}',
    drawTitle:  '{{ __("messages.caro_room_draw") }}',
    drawSub:    '{{ __("messages.caro_room_draw_sub") }}',
    winSub:     name => '{{ __("messages.caro_room_win_sub") }}'.replace(':name', name),
    loseSub:    name => '{{ __("messages.caro_room_lose_sub") }}'.replace(':name', name),
    oppLeft:    '{{ __("messages.caro_room_opp_left") }}',
    leaveAi:    '{{ __("messages.caro_room_leave_ai") }}',
    leavePvp:   '{{ __("messages.caro_room_leave_pvp") }}',
    timerEmpty: '—',
    timerSuffix:'{{ __("messages.caro_room_timer_suffix") }}' ,
    error:      '{{ __("messages.caro_error") }}',
};

// ── State ────────────────────────────────────────────────────────────────────
let state      = null;
let timerInt   = null;
let isDragging = false;
let dragStart  = { x:0, y:0 };
let offset     = { x:0, y:0 };   // canvas offset from logical (0,0) in px
let hoveredCell = null;

// ── Canvas setup ─────────────────────────────────────────────────────────────
const canvas  = document.getElementById('caroCanvas');
const ctx     = canvas.getContext('2d');
const wrapper = document.getElementById('boardWrapper');

function resizeCanvas() {
    const w = Math.round(wrapper.getBoundingClientRect().width) || 0;
    if (!w) return;
    const h = Math.min(window.innerHeight - 180, Math.max(480, Math.round(w * .75)));
    // Only reset canvas bitmap when dimensions actually change (reset clears the canvas)
    if (canvas.width !== w || canvas.height !== h) {
        canvas.width  = w;
        canvas.height = h;
        wrapper.style.height = h + 'px';
    }
    redraw();
}

window.addEventListener('resize', resizeCanvas);

// Initial sizing: wait one animation frame then poll until wrapper has real width
function initCanvas() {
    const w = Math.round(wrapper.getBoundingClientRect().width);
    if (w > 0) { resizeCanvas(); return; }
    setTimeout(initCanvas, 50); // retry until layout is ready
}
requestAnimationFrame(initCanvas);

// ── Coordinate helpers ───────────────────────────────────────────────────────
// Logical (row, col) → canvas pixel (cx, cy) of cell center
function logicalToCanvas(row, col) {
    return {
        cx: canvas.width  / 2 + (col - 0) * CELL + offset.x,
        cy: canvas.height / 2 + (row - 0) * CELL + offset.y,
    };
}

// Canvas pixel → nearest logical cell
function canvasToLogical(px, py) {
    const col = Math.round((px - canvas.width  / 2 - offset.x) / CELL);
    const row = Math.round((py - canvas.height / 2 - offset.y) / CELL);
    return { row, col };
}

// ── Draw ─────────────────────────────────────────────────────────────────────
function redraw() {
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!state) return;

    const minRow = state.min_row - 1;
    const maxRow = state.max_row + 1;
    const minCol = state.min_col - 1;
    const maxCol = state.max_col + 1;

    // Draw grid lines
    ctx.strokeStyle = 'rgba(0,0,0,.25)';
    ctx.lineWidth   = 1;

    for (let r = minRow; r <= maxRow; r++) {
        const { cx: x1, cy: y1 } = logicalToCanvas(r, minCol);
        const { cx: x2 }         = logicalToCanvas(r, maxCol);
        ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y1); ctx.stroke();
    }
    for (let c = minCol; c <= maxCol; c++) {
        const { cx: x1, cy: y1 } = logicalToCanvas(minRow, c);
        const { cy: y2 }         = logicalToCanvas(maxRow, c);
        ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x1, y2); ctx.stroke();
    }

    // Draw board edges (outer border)
    ctx.strokeStyle = 'rgba(0,0,0,.5)';
    ctx.lineWidth   = 2;
    const tl = logicalToCanvas(minRow, minCol);
    const br = logicalToCanvas(maxRow, maxCol);
    ctx.strokeRect(tl.cx, tl.cy, br.cx - tl.cx, br.cy - tl.cy);

    // Draw winning cells highlight
    const winSet = new Set((state.winning_cells ?? []).map(([r,c]) => `${r},${c}`));

    // Draw stones
    const board = {};
    for (const [r, c, s] of (state.moves ?? [])) board[`${r},${c}`] = s;

    for (const [r, c, sym] of (state.moves ?? [])) {
        const { cx, cy } = logicalToCanvas(r, c);
        const isWin = winSet.has(`${r},${c}`);
        drawStone(cx, cy, sym, isWin);
    }

    // Draw hover preview
    if (hoveredCell && state.status === 'playing' && state.current_player === MY_SYM) {
        const key = `${hoveredCell.row},${hoveredCell.col}`;
        if (!board[key]) {
            const { cx, cy } = logicalToCanvas(hoveredCell.row, hoveredCell.col);
            drawStone(cx, cy, MY_SYM, false, 0.4);
        }
    }
}

function drawStone(cx, cy, sym, isWin, alpha = 1) {
    ctx.globalAlpha = alpha;
    ctx.beginPath();
    ctx.arc(cx, cy, STONE_R, 0, Math.PI * 2);

    if (sym === 'X') {
        // White stone
        const grad = ctx.createRadialGradient(cx - 4, cy - 4, 2, cx, cy, STONE_R);
        grad.addColorStop(0, isWin ? '#ffd700' : '#ffffff');
        grad.addColorStop(1, isWin ? '#e5a000' : '#c0c0c0');
        ctx.fillStyle = grad;
    } else {
        // Black stone
        const grad = ctx.createRadialGradient(cx - 4, cy - 4, 2, cx, cy, STONE_R);
        grad.addColorStop(0, isWin ? '#ffd700' : '#555');
        grad.addColorStop(1, isWin ? '#9a7000' : '#111');
        ctx.fillStyle = grad;
    }
    ctx.fill();
    ctx.strokeStyle = isWin ? '#ffd700' : 'rgba(0,0,0,.4)';
    ctx.lineWidth   = isWin ? 2.5 : 1;
    ctx.stroke();
    ctx.globalAlpha = 1;
}

// ── Mouse / touch handling ────────────────────────────────────────────────────
let mouseDown = false;
let dragDist  = 0;

canvas.addEventListener('mousedown', e => {
    mouseDown = true; isDragging = false; dragDist = 0;
    dragStart = { x: e.clientX - offset.x, y: e.clientY - offset.y };
});

canvas.addEventListener('mousemove', e => {
    if (mouseDown) {
        const dx = e.clientX - offset.x - dragStart.x;
        const dy = e.clientY - offset.y - dragStart.y;
        dragDist = Math.sqrt(dx*dx + dy*dy);
        if (dragDist > 4) {
            isDragging = true;
            offset.x   = e.clientX - dragStart.x;
            offset.y   = e.clientY - dragStart.y;
            redraw();
        }
    }
    const rect = canvas.getBoundingClientRect();
    hoveredCell = canvasToLogical(e.clientX - rect.left, e.clientY - rect.top);
    if (!isDragging) redraw();
});

canvas.addEventListener('mouseup', e => {
    mouseDown = false;
    if (!isDragging && state?.status === 'playing' && state.current_player === MY_SYM) {
        const rect = canvas.getBoundingClientRect();
        const { row, col } = canvasToLogical(e.clientX - rect.left, e.clientY - rect.top);
        placeMove(row, col);
    }
    isDragging = false;
});

canvas.addEventListener('mouseleave', () => { mouseDown = false; hoveredCell = null; redraw(); });

// Touch support
let touchStart = null;
canvas.addEventListener('touchstart', e => {
    e.preventDefault();
    touchStart = { x: e.touches[0].clientX, y: e.touches[0].clientY, ox: offset.x, oy: offset.y };
    isDragging = false; dragDist = 0;
}, { passive: false });

canvas.addEventListener('touchmove', e => {
    e.preventDefault();
    if (!touchStart) return;
    const dx = e.touches[0].clientX - touchStart.x;
    const dy = e.touches[0].clientY - touchStart.y;
    dragDist = Math.sqrt(dx*dx + dy*dy);
    if (dragDist > 6) {
        isDragging = true;
        offset.x   = touchStart.ox + dx;
        offset.y   = touchStart.oy + dy;
        redraw();
    }
}, { passive: false });

canvas.addEventListener('touchend', e => {
    if (!isDragging && touchStart && state?.status === 'playing' && state.current_player === MY_SYM) {
        const rect = canvas.getBoundingClientRect();
        const tx   = touchStart.x - rect.left;
        const ty   = touchStart.y - rect.top;
        const { row, col } = canvasToLogical(tx, ty);
        placeMove(row, col);
    }
    touchStart = null; isDragging = false;
});

// ── Game logic ────────────────────────────────────────────────────────────────
function applyState(s) {
    state = s;
    updateStatus();
    updateMoveLog();
    redraw();
}

function updateStatus() {
    if (!state) return;
    const statusEl = document.getElementById('statusText');
    const overlay  = document.getElementById('resultOverlay');

    // Update player names
    if (state.player_x?.name) document.getElementById('nameX').textContent = state.player_x.name;
    if (state.player_o?.name) document.getElementById('nameO').textContent = state.player_o.name;

    // Highlight current turn tag
    document.getElementById('tagX').style.borderColor = (state.current_player === 'X' && state.status === 'playing') ? '#10b981' : '';
    document.getElementById('tagO').style.borderColor = (state.current_player === 'O' && state.status === 'playing') ? '#10b981' : '';

    if (state.status === 'finished') {
        clearInterval(timerInt);
        document.getElementById('timerLabel').textContent = TEXT.timerEmpty;
        document.getElementById('timerFill').style.width = '0%';

        let title, sub;
        if (state.winner === 'X' || state.winner === 'O') {
            const winName = state.winner === 'X' ? state.player_x?.name : state.player_o?.name;
            if (state.winner === MY_SYM) {
                title = TEXT.winTitle; sub = TEXT.winSub(winName);
            } else {
                title = TEXT.loseTitle; sub = TEXT.loseSub(winName);
            }
        } else {
            title = TEXT.drawTitle; sub = TEXT.drawSub;
        }

        document.getElementById('resultTitle').textContent = title;
        document.getElementById('resultSub').textContent   = sub;
        overlay.style.display = 'flex';
        statusEl.textContent  = title;
        return;
    }

    overlay.style.display = 'none';

    if (state.current_player === MY_SYM) {
        statusEl.className   = 'status-text';
        statusEl.textContent = TEXT.myTurn();
    } else if (IS_AI && state.current_player === 'O') {
        statusEl.className   = 'status-text thinking';
        statusEl.textContent = TEXT.aiThinking();
    } else {
        statusEl.className   = 'status-text';
        const oppName = MY_SYM === 'X' ? state.player_o?.name : state.player_x?.name;
        statusEl.textContent = TEXT.waitOpp(oppName);
    }

    // Timer
    startTimer();
}

function startTimer() {
    clearInterval(timerInt);
    if (!state?.turn_deadline || state.status !== 'playing') return;

    const deadline = state.turn_deadline * 1000;

    timerInt = setInterval(() => {
        const left = Math.max(0, deadline - Date.now());
        const pct  = (left / TURN_MS) * 100;
        const secs = Math.ceil(left / 1000);
        document.getElementById('timerLabel').textContent = secs + TEXT.timerSuffix;
        const fill = document.getElementById('timerFill');
        fill.style.width = pct + '%';
        fill.style.background = secs <= 10 ? '#ef4444' : secs <= 20 ? '#f59e0b' : '#10b981';

        if (left <= 0) {
            clearInterval(timerInt);
            // If it's not my turn (and not AI thinking), request timeout
            if (state.current_player !== MY_SYM && !IS_AI) {
                requestTimeout();
            }
        }
    }, 500);
}

function updateMoveLog() {
    const list = document.getElementById('moveList');
    const moves = state?.moves ?? [];
    list.innerHTML = moves.slice().reverse().slice(0, 50).map((m, i) => {
        const num = moves.length - i;
        const sym = m[2];
        return `<div class="move-item"><span class="sym">${sym}</span> &nbsp;(${m[0]}, ${m[1]})</div>`;
    }).join('');
}

async function placeMove(row, col) {
    const r = await fetch(`/entertainment/caro/${TABLE_ID}/move`, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({ row, col })
    });
    const d = await r.json();
    if (d.error) showToast(d.error ?? TEXT.error);
    else if (d.state) applyState(d.state);
}

async function rematch() {
    document.getElementById('resultOverlay').style.display = 'none';
    const r = await fetch(`/entertainment/caro/${TABLE_ID}/rematch`, {
        method:'POST', headers:{'X-CSRF-TOKEN':CSRF}
    });
    const d = await r.json();
    if (d.error) { showToast(d.error); document.getElementById('resultOverlay').style.display = 'flex'; }
    else if (d.state) applyState(d.state);
}

async function requestTimeout() {
    const r = await fetch(`/entertainment/caro/${TABLE_ID}/timeout`, {
        method:'POST', headers:{'X-CSRF-TOKEN':CSRF}
    });
    const d = await r.json();
    if (d.state) applyState(d.state);
}

async function leaveTable() {
    const msg = IS_AI ? TEXT.leaveAi : TEXT.leavePvp;
    if (!confirm(msg)) return;
    const r = await fetch(`/entertainment/caro/${TABLE_ID}/leave`, {
        method:'DELETE', headers:{'X-CSRF-TOKEN':CSRF}
    });
    window.location = '{{ route("entertainment.caro") }}';
}

// ── Real-time ─────────────────────────────────────────────────────────────────
window.Echo?.channel('caro.' + TABLE_ID).listen('.room.updated', e => {
    if (e.type === 'move_made' || e.type === 'game_started' || e.type === 'game_over') {
        if (e.state) applyState(e.state);
    } else if (e.type === 'player_left') {
        showToast(TEXT.oppLeft);
    }
});

// ── Init ──────────────────────────────────────────────────────────────────────
function showToast(msg, ms=3500) {
    const t = document.getElementById('toast');
    t.textContent = msg; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', ms);
}

document.getElementById('statusText').textContent = TEXT.loading;

// Load initial state
(async () => {
    const r = await fetch(`/entertainment/caro/${TABLE_ID}/state`);
    const d = await r.json();
    if (d.status !== 'waiting') applyState(d);
    else {
        document.getElementById('statusText').textContent = TEXT.waitLobby;
        // Update player names from waiting state
        if (d.player_x) document.getElementById('nameX').textContent = d.player_x.name ?? '?';
    }
    // Center viewport on existing moves or at (0,0)
    offset = { x:0, y:0 };
    redraw();
})();
</script>
@endpush
@endsection
