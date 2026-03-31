@extends('layouts.admin')
@section('title', 'Thư Viện')

@push('styles')
<style>
.lib-hall-title {
    font-size: 28px; font-weight: 900; color: #fff;
    text-align: center; margin-bottom: 8px; letter-spacing: 1px;
    text-shadow: 0 2px 8px rgba(0,0,0,.5);
}
.lib-hall-sub {
    text-align: center; font-size: 13px; color: rgba(255,255,255,.35);
    letter-spacing: 2px; margin-bottom: 24px;
}
.lib-search-wrap {
    max-width: 560px; margin: 0 auto 36px;
}
.lib-search-input {
    background: rgba(0,0,0,.5) !important;
    border: 1px solid rgba(255,255,255,.12) !important;
    color: #fff !important;
}
.lib-search-input::placeholder { color: rgba(255,255,255,.3); }
.lib-search-input:focus { border-color: rgba(255,255,255,.35) !important; box-shadow: none !important; }
.lib-search-btn {
    background: rgba(255,255,255,.1); color: #fff;
    border: 1px solid rgba(255,255,255,.15); font-weight: 700;
}
.lib-search-btn:hover { background: rgba(255,255,255,.18); color: #fff; }

.lib-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 28px;
}

/* ── Category card (same pattern as ent-card) ── */
.lib-card {
    position: relative; border-radius: 20px; overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,.5);
    transition: transform .28s cubic-bezier(.34,1.56,.64,1), box-shadow .28s ease;
    text-decoration: none; display: block; min-height: 200px;
}
.lib-card:hover {
    transform: translateY(-8px) scale(1.01);
    box-shadow: 0 20px 60px rgba(0,0,0,.65);
    text-decoration: none;
}
.lib-card::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.04) 0%, transparent 60%);
    pointer-events: none;
}
.lib-card-stripe {
    height: 3px; width: 100%; position: absolute; top: 0; left: 0; right: 0;
}
.lib-card-deco-bg {
    position: absolute; right: -10px; top: 50%; transform: translateY(-50%);
    font-size: 110px; opacity: .09; line-height: 1;
    pointer-events: none; user-select: none;
}
.lib-card-body {
    position: relative; z-index: 2;
    padding: 28px 28px 24px;
    height: 100%; display: flex; flex-direction: column; justify-content: space-between;
}
.lib-card-tag {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700; letter-spacing: 1px;
    text-transform: uppercase; margin-bottom: 12px;
}
.lib-card-title { font-size: 24px; font-weight: 900; color: #fff; margin-bottom: 4px; }
.lib-card-count { font-size: 13px; color: rgba(255,255,255,.4); margin-bottom: 20px; }

.lib-enter-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; border-radius: 10px;
    font-weight: 800; font-size: 14px;
    text-decoration: none; transition: all .2s; color: #fff;
    align-self: flex-start;
}
.lib-enter-btn:hover { transform: scale(1.04); text-decoration: none; color: #fff; }

/* ── Per-category themes ── */
.lib-card-chara {
    background: linear-gradient(145deg, #1a0a0a 0%, #5a0000 55%, #1a0a0a 100%);
}
.lib-card-chara .lib-card-stripe  { background: linear-gradient(90deg,#8b0000,#ff4444,#8b0000); }
.lib-card-chara .lib-card-tag     { background: rgba(255,68,68,.15); color: #ff6666; border: 1px solid rgba(255,68,68,.3); }
.lib-card-chara .lib-enter-btn    { background: linear-gradient(90deg,#8b0000,#ff4444); }
.lib-card-chara .lib-card-deco-bg { color: #ff4444; }

.lib-card-arena {
    background: linear-gradient(145deg, #0a001a 0%, #3d0066 55%, #0a001a 100%);
}
.lib-card-arena .lib-card-stripe  { background: linear-gradient(90deg,#7c3aed,#c084fc,#7c3aed); }
.lib-card-arena .lib-card-tag     { background: rgba(192,132,252,.15); color: #c084fc; border: 1px solid rgba(192,132,252,.3); }
.lib-card-arena .lib-enter-btn    { background: linear-gradient(90deg,#6d28d9,#a855f7); }
.lib-card-arena .lib-card-deco-bg { color: #c084fc; }

.lib-card-gw {
    background: linear-gradient(145deg, #0a0500 0%, #4a2000 55%, #0a0500 100%);
}
.lib-card-gw .lib-card-stripe  { background: linear-gradient(90deg,#b45309,#f59e0b,#b45309); }
.lib-card-gw .lib-card-tag     { background: rgba(245,158,11,.15); color: #fbbf24; border: 1px solid rgba(245,158,11,.3); }
.lib-card-gw .lib-enter-btn    { background: linear-gradient(90deg,#92400e,#f59e0b); }
.lib-card-gw .lib-card-deco-bg { color: #f59e0b; }

.lib-card-dungeon {
    background: linear-gradient(145deg, #000a0a 0%, #003040 55%, #000a0a 100%);
}
.lib-card-dungeon .lib-card-stripe  { background: linear-gradient(90deg,#0e7490,#22d3ee,#0e7490); }
.lib-card-dungeon .lib-card-tag     { background: rgba(34,211,238,.15); color: #22d3ee; border: 1px solid rgba(34,211,238,.3); }
.lib-card-dungeon .lib-enter-btn    { background: linear-gradient(90deg,#0e7490,#22d3ee); color: #000; }
.lib-card-dungeon .lib-card-deco-bg { color: #22d3ee; }

.lib-card-general {
    background: linear-gradient(145deg, #0a0a0a 0%, #1e293b 55%, #0a0a0a 100%);
}
.lib-card-general .lib-card-stripe  { background: linear-gradient(90deg,#475569,#94a3b8,#475569); }
.lib-card-general .lib-card-tag     { background: rgba(148,163,184,.12); color: #94a3b8; border: 1px solid rgba(148,163,184,.25); }
.lib-card-general .lib-enter-btn    { background: linear-gradient(90deg,#334155,#64748b); }
.lib-card-general .lib-card-deco-bg { color: #94a3b8; }
</style>
@endpush

@section('content')
<h2 class="lib-hall-title"><i class="fas fa-book-open mr-2"></i>THƯ VIỆN</h2>
<div class="lib-hall-sub">Kiến thức · Hướng dẫn · Kinh nghiệm</div>

<div class="lib-search-wrap">
    <form method="GET" action="{{ route('library.index') }}">
        <div class="input-group">
            <input type="text" name="search" class="form-control lib-search-input"
                   placeholder="Tìm kiếm bài viết..." value="{{ request('search') }}">
            <div class="input-group-append">
                <button class="btn lib-search-btn" type="submit">
                    <i class="fas fa-search mr-1"></i>Tìm
                </button>
            </div>
        </div>
    </form>
</div>

<div class="lib-grid">

    {{-- Phát triển nhân vật --}}
    <a href="{{ route('library.category', 'character_development') }}" class="lib-card lib-card-chara">
        <div class="lib-card-stripe"></div>
        <div class="lib-card-deco-bg"><i class="fas fa-user-graduate"></i></div>
        <div class="lib-card-body">
            <div>
                <div class="lib-card-tag">Nhân Vật</div>
                <div class="lib-card-title">Phát Triển Nhân Vật</div>
                <div class="lib-card-count">
                    <i class="fas fa-file-alt mr-1"></i>{{ $counts['character_development'] }} bài viết
                </div>
            </div>
            <span class="lib-enter-btn">
                <i class="fas fa-arrow-right"></i> Xem ngay
            </span>
        </div>
    </a>

    {{-- Đấu trường --}}
    <a href="{{ route('library.category', 'arena_summary') }}" class="lib-card lib-card-arena">
        <div class="lib-card-stripe"></div>
        <div class="lib-card-deco-bg"><i class="fas fa-trophy"></i></div>
        <div class="lib-card-body">
            <div>
                <div class="lib-card-tag">Đấu Trường</div>
                <div class="lib-card-title">Tổng Kết Đấu Trường</div>
                <div class="lib-card-count">
                    <i class="fas fa-file-alt mr-1"></i>{{ $counts['arena_summary'] }} bài viết
                </div>
            </div>
            <span class="lib-enter-btn">
                <i class="fas fa-arrow-right"></i> Xem ngay
            </span>
        </div>
    </a>

    {{-- Bang chiến --}}
    <a href="{{ route('library.category', 'guild_war_experience') }}" class="lib-card lib-card-gw">
        <div class="lib-card-stripe"></div>
        <div class="lib-card-deco-bg"><i class="fas fa-fist-raised"></i></div>
        <div class="lib-card-body">
            <div>
                <div class="lib-card-tag">Bang Chiến</div>
                <div class="lib-card-title">Kinh Nghiệm Bang Chiến</div>
                <div class="lib-card-count">
                    <i class="fas fa-file-alt mr-1"></i>{{ $counts['guild_war_experience'] }} bài viết
                </div>
            </div>
            <span class="lib-enter-btn">
                <i class="fas fa-arrow-right"></i> Xem ngay
            </span>
        </div>
    </a>

    {{-- Hang động --}}
    <a href="{{ route('library.category', 'dungeon_summary') }}" class="lib-card lib-card-dungeon">
        <div class="lib-card-stripe"></div>
        <div class="lib-card-deco-bg"><i class="fas fa-dungeon"></i></div>
        <div class="lib-card-body">
            <div>
                <div class="lib-card-tag">Hang Động</div>
                <div class="lib-card-title">Tổng Kết Hang Động</div>
                <div class="lib-card-count">
                    <i class="fas fa-file-alt mr-1"></i>{{ $counts['dungeon_summary'] }} bài viết
                </div>
            </div>
            <span class="lib-enter-btn">
                <i class="fas fa-arrow-right"></i> Xem ngay
            </span>
        </div>
    </a>

    {{-- Chung --}}
    <a href="{{ route('library.category', 'general') }}" class="lib-card lib-card-general">
        <div class="lib-card-stripe"></div>
        <div class="lib-card-deco-bg"><i class="fas fa-book"></i></div>
        <div class="lib-card-body">
            <div>
                <div class="lib-card-tag">Chung</div>
                <div class="lib-card-title">Kiến Thức Chung</div>
                <div class="lib-card-count">
                    <i class="fas fa-file-alt mr-1"></i>{{ $counts['general'] }} bài viết
                </div>
            </div>
            <span class="lib-enter-btn">
                <i class="fas fa-arrow-right"></i> Xem ngay
            </span>
        </div>
    </a>

</div>
@endsection
