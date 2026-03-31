@extends('layouts.admin')
@section('title', 'Thư Viện')

@section('content')
<div class="library-hero mb-4">
    <div class="library-hero-title">
        <i class="fas fa-book-open mr-2"></i>THƯ VIỆN
    </div>
    <div class="library-hero-sub">Kiến thức · Hướng dẫn · Kinh nghiệm</div>

    <!-- Search bar -->
    <form method="GET" action="{{ route('library.index') }}" class="library-search-form mt-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control library-search-input"
                   placeholder="Tìm kiếm bài viết..." value="{{ request('search') }}">
            <div class="input-group-append">
                <button class="btn library-search-btn" type="submit">
                    <i class="fas fa-search mr-1"></i>Tìm
                </button>
            </div>
        </div>
    </form>
</div>

<div class="row">
    @php
    $categoryStyles = [
        'character_development' => ['grad' => 'linear-gradient(135deg,#1a0a0a 0%,#5a0000 60%,#8b0000 100%)', 'accent' => '#ff4444'],
        'arena_summary'         => ['grad' => 'linear-gradient(135deg,#0a0a1a 0%,#1a003a 60%,#3d0066 100%)', 'accent' => '#c084fc'],
        'guild_war_experience'  => ['grad' => 'linear-gradient(135deg,#0a0500 0%,#3a1a00 60%,#6b3500 100%)', 'accent' => '#f59e0b'],
        'dungeon_summary'       => ['grad' => 'linear-gradient(135deg,#000a0a 0%,#002020 60%,#004040 100%)', 'accent' => '#22d3ee'],
        'general'               => ['grad' => 'linear-gradient(135deg,#0a0a0a 0%,#1a1a2e 60%,#16213e 100%)', 'accent' => '#6b7280'],
    ];
    @endphp

    @foreach(\App\Models\LibraryArticle::CATEGORIES as $key => $label)
    @php
        $count = $counts[$key] ?? 0;
        $icon  = \App\Models\LibraryArticle::CATEGORY_ICONS[$key];
        $style = $categoryStyles[$key];
    @endphp
    <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
        <a href="{{ route('library.category', $key) }}" class="lib-category-card" style="background:{{ $style['grad'] }};--accent:{{ $style['accent'] }};">
            <div class="lib-category-icon"><i class="{{ $icon }}"></i></div>
            <div class="lib-category-name">{{ $label }}</div>
            <div class="lib-category-count">{{ $count }} bài viết</div>
            <div class="lib-category-arrow"><i class="fas fa-arrow-right"></i></div>
            <div class="lib-category-glow"></div>
        </a>
    </div>
    @endforeach
</div>

<style>
.library-hero {
    background: linear-gradient(135deg, #0d0000 0%, #1a0000 40%, #2a0000 100%);
    border: 1px solid #5a0000;
    border-radius: 12px;
    padding: 28px 24px 20px;
    position: relative;
    overflow: hidden;
}
.library-hero::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #8b0000, #ff4444, #8b0000);
}
.library-hero-title {
    font-size: 1.8rem; font-weight: 900; color: #ff4444;
    letter-spacing: 3px; text-shadow: 0 0 20px rgba(255,68,68,.4);
}
.library-hero-sub { font-size: 13px; color: rgba(255,255,255,.4); letter-spacing: 2px; }
.library-search-input {
    background: rgba(0,0,0,.6) !important; border: 1px solid #5a0000 !important;
    color: #fff !important;
}
.library-search-input::placeholder { color: rgba(255,255,255,.3); }
.library-search-input:focus { border-color: #ff4444 !important; box-shadow: 0 0 0 2px rgba(255,68,68,.2) !important; }
.library-search-btn {
    background: #8b0000; color: #fff; border: 1px solid #5a0000;
    font-weight: 700;
}
.library-search-btn:hover { background: #ff4444; color: #fff; }

.lib-category-card {
    display: block;
    position: relative;
    border-radius: 14px;
    padding: 24px 20px 20px;
    min-height: 140px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.06);
    box-shadow: 0 4px 24px rgba(0,0,0,.5);
    text-decoration: none;
    transition: transform .2s, box-shadow .2s;
}
.lib-category-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 32px rgba(0,0,0,.7);
    text-decoration: none;
}
.lib-category-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: var(--accent);
    opacity: .8;
}
.lib-category-glow {
    position: absolute; bottom: -20px; right: -20px;
    width: 80px; height: 80px; border-radius: 50%;
    background: var(--accent);
    opacity: .08;
    filter: blur(20px);
}
.lib-category-icon {
    font-size: 2rem; color: var(--accent);
    margin-bottom: 10px;
    text-shadow: 0 0 16px rgba(255,255,255,.2);
}
.lib-category-name {
    font-size: 15px; font-weight: 700; color: #fff;
    margin-bottom: 4px;
}
.lib-category-count {
    font-size: 12px; color: rgba(255,255,255,.4);
}
.lib-category-arrow {
    position: absolute; bottom: 14px; right: 16px;
    color: var(--accent); font-size: 16px; opacity: .6;
    transition: opacity .2s, transform .2s;
}
.lib-category-card:hover .lib-category-arrow {
    opacity: 1; transform: translateX(4px);
}
</style>
@endsection
