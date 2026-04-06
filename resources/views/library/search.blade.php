@extends('layouts.admin')
@section('title', __('messages.lib_search_title') . $search)

@section('content')
<div class="lib-cat-header mb-4" style="--accent:#ff4444;">
    <div class="lib-cat-header-inner">
        <a href="{{ route('library.index') }}" class="lib-back-btn">
            <i class="fas fa-arrow-left mr-1"></i>{{ __('messages.lib_breadcrumb') }}
        </a>
        <div class="lib-cat-title">
            <i class="fas fa-search mr-2"></i>{{ __('messages.lib_search_results') }} <em style="font-weight:400;">"{{ $search }}"</em>
        </div>
        <div class="lib-cat-count">{{ $articles->total() }} {{ __('messages.lib_articles_count') }}</div>
    </div>
    <div class="lib-cat-stripe"></div>
</div>

<form method="GET" action="{{ route('library.index') }}" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control library-search-input" value="{{ $search }}">
        <div class="input-group-append">
            <button class="btn library-search-btn" type="submit"><i class="fas fa-search mr-1"></i>{{ __('messages.lib_search_btn') }}</button>
        </div>
    </div>
</form>

@if($articles->isEmpty())
<div class="lib-empty"><i class="fas fa-inbox fa-2x mb-2"></i><br>{{ __('messages.lib_no_results') }}</div>
@else
<div class="row">
    @foreach($articles as $article)
    @php
    $accent = $article->categoryAccent();
    @endphp
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="lib-article-card" style="--accent:{{ $accent }};">
            <div class="lib-article-card-top"></div>
            <div class="lib-article-body">
                <div class="lib-article-cat-badge" style="color:{{ $accent }};font-size:10px;margin-bottom:6px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">
                    <i class="{{ $article->categoryIcon() }} mr-1"></i>{{ $article->categoryLabel() }}
                </div>
                <h6 class="lib-article-title">{{ $article->title }}</h6>
                <p class="lib-article-preview">{{ $article->preview(160) }}</p>
            </div>
            <div class="lib-article-footer">
                <span class="lib-article-meta">{{ $article->published_at?->format('d/m/Y') }}</span>
                <a href="{{ route('library.show', [$article->category, $article]) }}" class="lib-read-btn">
                    {{ __('messages.lib_read') }} <i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
{{ $articles->links() }}
@endif

<style>
.library-search-input { background: rgba(0,0,0,.6) !important; border: 1px solid #5a0000 !important; color: #fff !important; }
.library-search-input::placeholder { color: rgba(255,255,255,.3); }
.library-search-btn { background: #8b0000; color: #fff; border: 1px solid #5a0000; font-weight: 700; }
.library-search-btn:hover { background: #ff4444; color: #fff; }
.lib-cat-header { background: linear-gradient(135deg, #0d0000 0%, #1a0000 100%); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; overflow: hidden; position: relative; }
.lib-cat-stripe { position: absolute; bottom: 0; left: 0; right: 0; height: 2px; background: var(--accent); }
.lib-cat-header-inner { padding: 20px 20px 16px; }
.lib-back-btn { display: inline-block; font-size: 12px; color: rgba(255,255,255,.5); background: rgba(255,255,255,.06); border-radius: 20px; padding: 3px 10px; text-decoration: none; margin-bottom: 8px; border: 1px solid rgba(255,255,255,.1); }
.lib-back-btn:hover { color: #fff; text-decoration: none; }
.lib-cat-title { font-size: 1.4rem; font-weight: 800; color: var(--accent); }
.lib-cat-count { font-size: 12px; color: rgba(255,255,255,.35); margin-top: 2px; }
.lib-empty { text-align: center; padding: 60px; color: rgba(255,255,255,.3); background: rgba(0,0,0,.3); border-radius: 12px; }
.lib-article-card { position: relative; border-radius: 12px; overflow: hidden; background: linear-gradient(135deg, #0d0000 0%, #150000 100%); border: 1px solid rgba(255,255,255,.07); box-shadow: 0 2px 16px rgba(0,0,0,.4); display: flex; flex-direction: column; height: 100%; transition: transform .2s, box-shadow .2s; }
.lib-article-card:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(0,0,0,.6); }
.lib-article-card-top { height: 3px; background: var(--accent); opacity: .8; }
.lib-article-body { padding: 16px 16px 12px; flex: 1; }
.lib-article-title { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 8px; line-height: 1.4; }
.lib-article-preview { font-size: 12px; color: rgba(255,255,255,.45); line-height: 1.6; margin: 0; }
.lib-article-footer { padding: 10px 16px; border-top: 1px solid rgba(255,255,255,.06); display: flex; align-items: center; justify-content: space-between; }
.lib-article-meta { font-size: 11px; color: rgba(255,255,255,.3); }
.lib-read-btn { font-size: 12px; font-weight: 700; color: var(--accent); text-decoration: none; white-space: nowrap; }
.lib-read-btn:hover { color: #fff; text-decoration: none; }
</style>
@endsection
