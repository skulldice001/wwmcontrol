@extends('layouts.admin')
@section('title', 'Thư Viện · Quản lý')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center">
        <h3 class="card-title mr-3">Bài viết thư viện</h3>
        <div class="ml-auto d-flex gap-2" style="gap:8px;">
            <a href="{{ route('admin.library.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus mr-1"></i>Tạo bài viết
            </a>
        </div>
    </div>

    <!-- Status tabs -->
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" style="padding:0 15px;">
            <li class="nav-item">
                <a class="nav-link {{ !request('status') ? 'active' : '' }}"
                   href="{{ route('admin.library.index', array_merge(request()->except('status','page'), [])) }}">
                   Tất cả <span class="badge badge-secondary ml-1">{{ $counts['all'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') === 'draft' ? 'active' : '' }}"
                   href="{{ route('admin.library.index', array_merge(request()->except('status','page'), ['status'=>'draft'])) }}">
                   Bản nháp <span class="badge badge-warning ml-1">{{ $counts['draft'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request('status') === 'published' ? 'active' : '' }}"
                   href="{{ route('admin.library.index', array_merge(request()->except('status','page'), ['status'=>'published'])) }}">
                   Đã xuất bản <span class="badge badge-success ml-1">{{ $counts['published'] }}</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Filters -->
    <div class="card-header border-top-0">
        <form method="GET" action="{{ route('admin.library.index') }}" class="form-inline" style="gap:8px;flex-wrap:wrap;">
            @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm kiếm..." value="{{ request('search') }}" style="width:200px;">
            <select name="category" class="form-control form-control-sm" style="width:200px;">
                <option value="">-- Tất cả danh mục --</option>
                @foreach(\App\Models\LibraryArticle::CATEGORIES as $key => $label)
                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-search mr-1"></i>Lọc</button>
            <a href="{{ route('admin.library.index') }}" class="btn btn-sm btn-secondary">Xoá lọc</a>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th style="width:40%">Tiêu đề</th>
                    <th>Danh mục</th>
                    <th>Trạng thái</th>
                    <th>Nguồn</th>
                    <th>Ngày xuất bản</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                <tr>
                    <td>
                        <strong>{{ $article->title }}</strong>
                        @if($article->discord_message_id)
                        <small class="text-muted d-block"><i class="fab fa-discord"></i> {{ $article->discord_author }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $article->categoryColor() }}">
                            <i class="{{ $article->categoryIcon() }} mr-1"></i>{{ $article->categoryLabel() }}
                        </span>
                    </td>
                    <td>
                        @if($article->isPublished())
                        <span class="badge badge-success">Đã xuất bản</span>
                        @else
                        <span class="badge badge-warning">Bản nháp</span>
                        @endif
                    </td>
                    <td>
                        @if($article->discord_message_id)
                        <span class="badge badge-light" title="Discord"><i class="fab fa-discord"></i></span>
                        @else
                        <span class="badge badge-light"><i class="fas fa-pencil-alt"></i></span>
                        @endif
                    </td>
                    <td style="font-size:12px;">{{ $article->published_at ? $article->published_at->format('d/m/Y') : '—' }}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('admin.library.edit', $article) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($article->isPublished())
                            <form method="POST" action="{{ route('admin.library.unpublish', $article) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-warning btn-sm" title="Chuyển về nháp"><i class="fas fa-eye-slash"></i></button>
                            </form>
                            @else
                            <form method="POST" action="{{ route('admin.library.publish', $article) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-success btn-sm" title="Xuất bản"><i class="fas fa-check"></i></button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.library.destroy', $article) }}" class="d-inline"
                                  onsubmit="return confirm('Xoá bài viết này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-3">Không có bài viết nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
    <div class="card-footer">{{ $articles->links() }}</div>
    @endif
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title"><i class="fab fa-discord mr-1"></i>Import từ Discord</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">Channel: <code>1461845830737723597</code></p>
        <p class="text-muted mb-2">Chạy lệnh sau trong terminal để import bài từ Discord:</p>
        <pre class="bg-dark text-white p-3 rounded" style="font-size:13px;">php artisan library:import-discord</pre>
        <p class="text-muted" style="font-size:12px;">Các bài nhập sẽ ở trạng thái <strong>Bản nháp</strong>. Bạn cần biên tập và xuất bản thủ công.</p>
        <p class="text-muted" style="font-size:12px;">Options: <code>--limit=100</code> &nbsp;|&nbsp; <code>--category=general</code> &nbsp;|&nbsp; <code>--before=[message_id]</code></p>
    </div>
</div>
@endsection
