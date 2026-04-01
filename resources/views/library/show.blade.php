@extends('layouts.admin')
@section('title', $article->title)

@push('styles')
<style>
.lib-guide-wrap { max-width: 860px; margin: 0 auto; }

.lib-bc { font-size: 11px; color: #666; margin-bottom: 20px; display: flex; align-items: center; flex-wrap: wrap; gap: 4px; }
.lib-bc a { color: #888; text-decoration: none; }
.lib-bc a:hover { color: #fff; }
.lib-bc-sep { color: #444; }

/* ── Unified card: header + content as one block ── */
.lib-guide-card {
    background: #0e0606;
    border: 1px solid #2a1010;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(0,0,0,.7);
    margin-bottom: 24px;
}
.lib-guide-top-bar {
    height: 4px;
    background: linear-gradient(90deg, var(--accent), color-mix(in srgb, var(--accent) 50%, transparent));
}
.lib-guide-header {
    padding: 28px 32px 24px;
    border-bottom: 1px solid #1e0606;
    background: #120808;
}
.lib-guide-cat {
    display: inline-flex; align-items: center; gap: 6px;
    background: color-mix(in srgb, var(--accent) 12%, transparent);
    color: var(--accent); border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
    border-radius: 20px; padding: 3px 12px;
    font-size: 10px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase;
    margin-bottom: 14px;
}
.lib-guide-title {
    font-size: 1.75rem; font-weight: 900; color: #fff;
    line-height: 1.25; margin: 0 0 16px;
}
.lib-guide-meta {
    display: flex; align-items: center; flex-wrap: wrap; gap: 14px;
    font-size: 12px; color: #777;
    padding-top: 14px; border-top: 1px solid #1e0606;
}
.lib-guide-meta-item { display: flex; align-items: center; gap: 5px; }
.lib-guide-edit-btn {
    margin-left: auto; color: var(--accent); text-decoration: none;
    font-size: 11px; font-weight: 700;
    background: color-mix(in srgb, var(--accent) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--accent) 28%, transparent);
    border-radius: 6px; padding: 3px 10px;
}
.lib-guide-edit-btn:hover { color: #fff; text-decoration: none; }

/* ── Body ── */
.lib-guide-body {
    padding: 30px 32px 36px;
    font-size: 15px; line-height: 2; color: #ccc;
    word-break: break-word;
}
.lib-guide-excerpt {
    font-size: 14px; color: #aaa; font-style: italic;
    padding: 0 0 22px; margin-bottom: 24px;
    border-bottom: 1px solid #1e0606;
    border-left: 3px solid var(--accent); padding-left: 14px;
}
.lib-guide-body .lib-divider {
    border: none; border-top: 1px solid #2a1010; margin: 28px 0;
    position: relative;
}
.lib-guide-body .lib-divider::after {
    content: '◆'; position: absolute; top: -8px; left: 50%;
    transform: translateX(-50%); font-size: 10px;
    color: var(--accent); background: #0e0606; padding: 0 8px; opacity: .5;
}
.lib-guide-body .lib-img {
    max-width: 100%; border-radius: 10px; margin: 20px auto;
    display: block; box-shadow: 0 6px 28px rgba(0,0,0,.8);
    border: 1px solid #2a1010;
}
.lib-guide-body .lib-img-section-title {
    font-size: 10px; font-weight: 700; letter-spacing: 2.5px;
    text-transform: uppercase; color: #444;
    margin: 32px 0 14px; display: flex; align-items: center; gap: 8px;
}
.lib-guide-body .lib-img-section-title::before,
.lib-guide-body .lib-img-section-title::after {
    content: ''; flex: 1; height: 1px; background: #2a1010;
}

/* ── Back button ── */
.lib-guide-back {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 13px; font-weight: 600; color: #999;
    text-decoration: none; background: #160808;
    border: 1px solid #2a1010; border-radius: 22px; padding: 9px 20px;
    transition: all .2s;
}
.lib-guide-back:hover { color: #fff; background: #2a1212; border-color: var(--accent); text-decoration: none; }
</style>
@endpush

@section('content')
@php $accent = $article->categoryAccent(); @endphp

<div class="lib-guide-wrap" style="--accent:{{ $accent }};">

    <div class="lib-bc">
        <a href="{{ route('library.index') }}"><i class="fas fa-home"></i> Thư Viện</a>
        <span class="lib-bc-sep">›</span>
        <a href="{{ route('library.category', $category) }}">{{ $article->categoryLabel() }}</a>
        <span class="lib-bc-sep">›</span>
        <span style="color:#bbb;">{{ mb_substr($article->title, 0, 55) }}{{ mb_strlen($article->title) > 55 ? '…' : '' }}</span>
    </div>

    <div class="lib-guide-card">
        <div class="lib-guide-top-bar"></div>

        <div class="lib-guide-header">
            <div class="lib-guide-cat">
                <i class="{{ $article->categoryIcon() }}"></i>{{ $article->categoryLabel() }}
            </div>
            <h1 class="lib-guide-title">{{ $article->title }}</h1>
            <div class="lib-guide-meta">
                @if($article->discord_author)
                <span class="lib-guide-meta-item">
                    <i class="fab fa-discord" style="color:#5865F2;"></i> {{ $article->discord_author }}
                </span>
                @elseif($article->creator)
                <span class="lib-guide-meta-item">
                    <i class="fas fa-user-edit"></i> {{ $article->creator->name }}
                </span>
                @endif
                @if($article->published_at)
                <span class="lib-guide-meta-item">
                    <i class="fas fa-calendar-alt"></i> {{ $article->published_at->format('d/m/Y') }}
                </span>
                @endif
                @auth('staff')
                <a href="{{ route('admin.library.edit', $article) }}" class="lib-guide-edit-btn">
                    <i class="fas fa-edit mr-1"></i>Chỉnh sửa
                </a>
                @endauth
            </div>
        </div>

        <div class="lib-guide-body">
            @if($article->excerpt)
            <div class="lib-guide-excerpt">{{ $article->excerpt }}</div>
            @endif
            {!! $article->renderedContent() !!}
        </div>
    </div>

    <a href="{{ route('library.category', $category) }}" class="lib-guide-back">
        <i class="fas fa-arrow-left"></i> Quay lại danh mục
    </a>

</div>
@endsection
