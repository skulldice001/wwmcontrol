@extends('layouts.admin')
@section('title', \App\Models\LibraryArticle::CATEGORIES[$category] ?? $category)

@section('content')
@php
$accentMap = [
    'character_development' => '#ff4444',
    'arena_summary'         => '#c084fc',
    'guild_war_experience'  => '#f59e0b',
    'dungeon_summary'       => '#22d3ee',
    'general'               => '#9ca3af',
];
$accent = $accentMap[$category] ?? '#ff4444';
$icon   = \App\Models\LibraryArticle::CATEGORY_ICONS[$category] ?? 'fas fa-book';
$label  = \App\Models\LibraryArticle::CATEGORIES[$category] ?? $category;
@endphp

<div class="lib-cat-header mb-4" style="--accent:{{ $accent }};">
    <div class="lib-cat-header-inner">
        <a href="{{ route('library.index') }}" class="lib-back-btn">
            <i class="fas fa-arrow-left mr-1"></i>Thư Viện
        </a>
        <div class="lib-cat-title">
            <i class="{{ $icon }} mr-2"></i>{{ $label }}
        </div>
        <div class="lib-cat-count">{{ $articles->total() }} bài viết</div>
    </div>
    <div class="lib-cat-stripe"></div>
</div>

@if($articles->isEmpty())
<div class="lib-empty">
    <i class="fas fa-inbox fa-2x mb-2"></i><br>Chưa có bài viết nào.
</div>
@else
<div class="lib-list">
    @foreach($articles as $article)
    <a href="{{ route('library.show', [$category, $article]) }}" class="lib-list-item">
        <div class="lib-list-accent" style="background:{{ $accent }};"></div>
        <div class="lib-list-title">{{ $article->title }}</div>
        <div class="lib-list-meta">
            @if($article->discord_author)
                <i class="fab fa-discord mr-1" style="color:#5865F2;"></i>{{ $article->discord_author }}
            @elseif($article->creator)
                <i class="fas fa-user mr-1"></i>{{ $article->creator->name }}
            @endif
            @if($article->published_at)
                <span class="ml-2">{{ $article->published_at->format('d/m/Y') }}</span>
            @endif
        </div>
        <i class="fas fa-chevron-right lib-list-arrow"></i>
    </a>
    @endforeach
</div>
{{ $articles->links() }}
@endif

<style>
.lib-cat-header {
    background: linear-gradient(135deg, #0d0000 0%, #1a0000 100%);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px; overflow: hidden; position: relative;
}
.lib-cat-stripe {
    position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
    background: var(--accent);
}
.lib-cat-header-inner { padding: 20px 20px 16px; }
.lib-back-btn {
    display: inline-block; font-size: 12px; color: rgba(255,255,255,.5);
    background: rgba(255,255,255,.06); border-radius: 20px; padding: 3px 10px;
    text-decoration: none; margin-bottom: 8px;
    border: 1px solid rgba(255,255,255,.1);
}
.lib-back-btn:hover { color: #fff; text-decoration: none; background: rgba(255,255,255,.1); }
.lib-cat-title { font-size: 1.4rem; font-weight: 800; color: var(--accent); letter-spacing: 1px; }
.lib-cat-count { font-size: 12px; color: rgba(255,255,255,.35); margin-top: 2px; }

.lib-empty {
    text-align: center; padding: 60px; color: rgba(255,255,255,.3);
    background: rgba(0,0,0,.3); border-radius: 12px;
}

/* ── Article list ── */
.lib-list {
    display: flex; flex-direction: column; gap: 6px;
}
.lib-list-item {
    display: flex; align-items: center; gap: 14px;
    background: rgba(0,0,0,.35);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px;
    padding: 14px 18px;
    text-decoration: none;
    transition: background .15s, border-color .15s;
}
.lib-list-item:hover {
    background: rgba(255,255,255,.06);
    border-color: rgba(255,255,255,.14);
    text-decoration: none;
}
.lib-list-accent {
    width: 3px; height: 20px; border-radius: 2px; flex-shrink: 0; opacity: .75;
}
.lib-list-title {
    flex: 1; font-size: 14px; font-weight: 600; color: #fff;
    line-height: 1.4;
}
.lib-list-meta {
    font-size: 11px; color: rgba(255,255,255,.3);
    white-space: nowrap; flex-shrink: 0;
}
.lib-list-arrow {
    font-size: 11px; color: rgba(255,255,255,.2); flex-shrink: 0;
}
.lib-list-item:hover .lib-list-arrow { color: rgba(255,255,255,.5); }
</style>
@endsection
