@extends('layouts.admin')
@section('title', $article->title)

@push('styles')
<style>
.lib-breadcrumb { font-size: 12px; color: #888; }
.lib-breadcrumb a { color: #aaa; text-decoration: none; }
.lib-breadcrumb a:hover { color: #fff; }
.lib-bc-sep { margin: 0 6px; color: #555; }

.lib-article-page {
    background: #110000;
    border: 1px solid #2e1010;
    border-radius: 14px; overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 8px 32px rgba(0,0,0,.6);
}
.lib-article-page-stripe { height: 4px; background: var(--accent); }

.lib-article-page-header {
    padding: 28px 32px 22px;
    border-bottom: 1px solid #1e0a0a;
    background: #160000;
}
.lib-article-page-cat {
    font-size: 11px; font-weight: 700; letter-spacing: 2px;
    color: var(--accent); text-transform: uppercase; margin-bottom: 10px;
    opacity: .85;
}
.lib-article-page-title {
    font-size: 1.7rem; font-weight: 800; color: #fff;
    line-height: 1.3; margin-bottom: 14px;
}
.lib-article-page-meta {
    font-size: 12px; color: #888;
    display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
}
.lib-edit-link { color: var(--accent); text-decoration: none; font-weight: 600; }
.lib-edit-link:hover { color: #fff; }

.lib-article-excerpt {
    padding: 16px 32px;
    border-bottom: 1px solid #1e0a0a;
    font-size: 14px; color: #bbb;
    font-style: italic;
    border-left: 4px solid var(--accent);
    background: #0d0000;
}

.lib-article-content {
    padding: 28px 32px;
    font-size: 15px; line-height: 2;
    color: #ddd;
    word-break: break-word;
}
.lib-article-content .lib-img {
    max-width: 100%; border-radius: 10px;
    margin: 16px 0; display: block;
    box-shadow: 0 4px 24px rgba(0,0,0,.7);
    border: 1px solid #2a1010;
}
.lib-article-content .lib-divider {
    border: none; border-top: 1px solid #2a1010; margin: 24px 0;
}
.lib-article-content .lib-img-section-title {
    font-size: 10px; font-weight: 700; letter-spacing: 2px;
    text-transform: uppercase; color: #555;
    margin: 28px 0 12px;
}

.lib-back-bottom {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 13px; font-weight: 600;
    color: #aaa; text-decoration: none;
    background: #1a0a0a; border-radius: 20px;
    padding: 8px 18px; border: 1px solid #2e1a1a;
    transition: all .2s;
}
.lib-back-bottom:hover { color: #fff; background: #2a1212; border-color: #5a2020; text-decoration: none; }
</style>
@endpush

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

<div class="lib-breadcrumb mb-4">
    <a href="{{ route('library.index') }}"><i class="fas fa-home mr-1"></i>Thư Viện</a>
    <span class="lib-bc-sep">›</span>
    <a href="{{ route('library.category', $category) }}">{{ $article->categoryLabel() }}</a>
    <span class="lib-bc-sep">›</span>
    <span>{{ mb_substr($article->title, 0, 50) }}{{ mb_strlen($article->title) > 50 ? '…' : '' }}</span>
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
            <span><i class="fas fa-calendar mr-1"></i>{{ $article->published_at->format('d/m/Y H:i') }}</span>
            @endif
            @auth('staff')
            <a href="{{ route('admin.library.edit', $article) }}" class="lib-edit-link ml-2">
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
    <i class="fas fa-arrow-left"></i> Quay lại danh mục
</a>
@endsection
