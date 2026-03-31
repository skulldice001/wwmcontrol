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
<div class="row">
    @foreach($articles as $article)
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="lib-article-card" style="--accent:{{ $accent }};">
            <div class="lib-article-card-top"></div>
            <div class="lib-article-body">
                <h6 class="lib-article-title">{{ $article->title }}</h6>
                <p class="lib-article-preview">{{ $article->preview(180) }}</p>
            </div>
            <div class="lib-article-footer">
                <span class="lib-article-meta">
                    @if($article->discord_author)
                    <i class="fab fa-discord mr-1"></i>{{ $article->discord_author }}
                    @elseif($article->creator)
                    <i class="fas fa-user mr-1"></i>{{ $article->creator->name }}
                    @endif
                    @if($article->published_at)
                    · {{ $article->published_at->format('d/m/Y') }}
                    @endif
                </span>
                <a href="{{ route('library.show', [$category, $article]) }}" class="lib-read-btn">
                    Đọc <i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
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

.lib-article-card {
    position: relative; border-radius: 12px; overflow: hidden;
    background: linear-gradient(135deg, #0d0000 0%, #150000 100%);
    border: 1px solid rgba(255,255,255,.07);
    box-shadow: 0 2px 16px rgba(0,0,0,.4);
    display: flex; flex-direction: column;
    height: 100%;
    transition: transform .2s, box-shadow .2s;
}
.lib-article-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,.6); }
.lib-article-card-top {
    height: 3px; background: var(--accent); opacity: .8;
}
.lib-article-body { padding: 16px 16px 12px; flex: 1; }
.lib-article-title {
    font-size: 14px; font-weight: 700; color: #fff;
    margin-bottom: 8px; line-height: 1.4;
}
.lib-article-preview {
    font-size: 12px; color: rgba(255,255,255,.45); line-height: 1.6; margin: 0;
}
.lib-article-footer {
    padding: 10px 16px;
    border-top: 1px solid rgba(255,255,255,.06);
    display: flex; align-items: center; justify-content: space-between;
}
.lib-article-meta { font-size: 11px; color: rgba(255,255,255,.3); }
.lib-read-btn {
    font-size: 12px; font-weight: 700; color: var(--accent);
    text-decoration: none; white-space: nowrap;
}
.lib-read-btn:hover { color: #fff; text-decoration: none; }
</style>
@endsection
