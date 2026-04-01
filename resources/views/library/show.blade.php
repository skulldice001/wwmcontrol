@extends('layouts.admin')
@section('title', $article->title)

@section('content')
@php
$accentMap = [
    'character_development' => '#ff4444',
    'arena_summary'         => '#c084fc',
    'guild_war_experience'  => '#f59e0b',
    'dungeon_summary'       => '#22d3ee',
    'general'               => '#9ca3af',
];
$accent = $accentMap[$article->category] ?? '#ff4444';
@endphp

<!-- Breadcrumb -->
<div class="lib-breadcrumb mb-3">
    <a href="{{ route('library.index') }}"><i class="fas fa-home mr-1"></i>Thư Viện</a>
    <span class="lib-bc-sep">›</span>
    <a href="{{ route('library.category', $category) }}">{{ $article->categoryLabel() }}</a>
    <span class="lib-bc-sep">›</span>
    <span>{{ mb_substr($article->title, 0, 40) }}{{ mb_strlen($article->title) > 40 ? '…' : '' }}</span>
</div>

<div class="lib-article-page" style="--accent:{{ $accent }};">
    <div class="lib-article-page-stripe"></div>
    <div class="lib-article-page-header">
        <div class="lib-article-page-cat">
            <i class="{{ $article->categoryIcon() }} mr-1"></i>{{ $article->categoryLabel() }}
        </div>
        <h1 class="lib-article-page-title">{{ $article->title }}</h1>
        <div class="lib-article-page-meta">
            @if($article->discord_author)
            <span><i class="fab fa-discord mr-1" style="color:#5865F2;"></i>{{ $article->discord_author }}</span>
            @elseif($article->creator)
            <span><i class="fas fa-user-edit mr-1"></i>{{ $article->creator->name }}</span>
            @endif
            @if($article->published_at)
            <span class="ml-2"><i class="fas fa-calendar mr-1"></i>{{ $article->published_at->format('d/m/Y H:i') }}</span>
            @endif
            @auth('staff')
            <a href="{{ route('admin.library.edit', $article) }}" class="lib-edit-link ml-3">
                <i class="fas fa-edit mr-1"></i>Chỉnh sửa
            </a>
            @endauth
        </div>
    </div>

    @if($article->excerpt)
    <div class="lib-article-excerpt">{{ $article->excerpt }}</div>
    @endif

    <div class="lib-article-content">{!! $article->renderedContent() !!}</div>
</div>

<a href="{{ route('library.category', $category) }}" class="lib-back-bottom">
    <i class="fas fa-arrow-left mr-1"></i>Quay lại danh mục
</a>

<style>
.lib-breadcrumb {
    font-size: 12px; color: rgba(255,255,255,.4);
}
.lib-breadcrumb a { color: rgba(255,255,255,.5); text-decoration: none; }
.lib-breadcrumb a:hover { color: #fff; }
.lib-bc-sep { margin: 0 6px; opacity: .3; }

.lib-article-page {
    background: linear-gradient(135deg, #0d0000 0%, #130000 100%);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 14px; overflow: hidden; position: relative;
    margin-bottom: 20px;
}
.lib-article-page-stripe {
    height: 3px; background: var(--accent);
}
.lib-article-page-header {
    padding: 24px 28px 20px;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.lib-article-page-cat {
    font-size: 11px; font-weight: 700; letter-spacing: 1.5px;
    color: var(--accent); text-transform: uppercase; margin-bottom: 8px;
}
.lib-article-page-title {
    font-size: 1.6rem; font-weight: 800; color: #fff;
    line-height: 1.3; margin-bottom: 12px;
    text-shadow: 0 2px 12px rgba(0,0,0,.5);
}
.lib-article-page-meta {
    font-size: 12px; color: rgba(255,255,255,.4);
    display: flex; align-items: center; flex-wrap: wrap; gap: 4px;
}
.lib-edit-link { color: var(--accent); text-decoration: none; font-weight: 600; }
.lib-edit-link:hover { color: #fff; }

.lib-article-excerpt {
    padding: 16px 28px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    font-size: 14px; color: rgba(255,255,255,.6);
    font-style: italic;
    border-left: 3px solid var(--accent);
    margin: 0;
    background: rgba(0,0,0,.2);
}

.lib-article-content {
    padding: 24px 28px;
    font-size: 15px; line-height: 1.9;
    color: rgba(255,255,255,.82);
    word-break: break-word;
}
.lib-article-content .lib-img {
    max-width: 100%; border-radius: 10px;
    margin: 14px 0; display: block;
    box-shadow: 0 4px 20px rgba(0,0,0,.5);
}
.lib-article-content .lib-divider {
    border: none; border-top: 1px solid rgba(255,255,255,.1); margin: 20px 0;
}
.lib-article-content .lib-img-section-title {
    font-size: 11px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: rgba(255,255,255,.3);
    margin: 24px 0 12px;
}

.lib-back-bottom {
    display: inline-block; font-size: 13px; font-weight: 600;
    color: rgba(255,255,255,.5); text-decoration: none;
    background: rgba(255,255,255,.06); border-radius: 20px;
    padding: 6px 14px; border: 1px solid rgba(255,255,255,.1);
    transition: all .2s;
}
.lib-back-bottom:hover { color: #fff; background: rgba(255,255,255,.12); text-decoration: none; }
</style>
@endsection
