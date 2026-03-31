@extends('layouts.admin')
@section('title', 'Chỉnh sửa bài viết')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
@endif

<div class="row">
    <div class="col-lg-9">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-1"></i>Chỉnh sửa bài viết</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.library.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Quay lại
                    </a>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.library.update', $article) }}">
                @csrf @method('PUT')
                <div class="card-body">
                    @include('admin.library._form')
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save mr-1"></i>Lưu thay đổi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-3">
        <!-- Status card -->
        <div class="card card-outline {{ $article->isPublished() ? 'card-success' : 'card-warning' }}">
            <div class="card-header">
                <h3 class="card-title">Trạng thái</h3>
            </div>
            <div class="card-body">
                <p>
                    @if($article->isPublished())
                    <span class="badge badge-success badge-lg">Đã xuất bản</span>
                    @if($article->published_at)
                    <small class="text-muted d-block mt-1">{{ $article->published_at->format('d/m/Y H:i') }}</small>
                    @endif
                    @else
                    <span class="badge badge-warning badge-lg">Bản nháp</span>
                    @endif
                </p>

                @if($article->isPublished())
                <form method="POST" action="{{ route('admin.library.unpublish', $article) }}">
                    @csrf
                    <button class="btn btn-warning btn-sm btn-block">
                        <i class="fas fa-eye-slash mr-1"></i>Chuyển về nháp
                    </button>
                </form>
                <a href="{{ route('library.show', [$article->category, $article]) }}"
                   target="_blank" class="btn btn-outline-success btn-sm btn-block mt-2">
                    <i class="fas fa-external-link-alt mr-1"></i>Xem bài
                </a>
                @else
                <form method="POST" action="{{ route('admin.library.publish', $article) }}">
                    @csrf
                    <button class="btn btn-success btn-sm btn-block">
                        <i class="fas fa-check mr-1"></i>Xuất bản ngay
                    </button>
                </form>
                @endif
            </div>
        </div>

        @if($article->discord_message_id)
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fab fa-discord mr-1"></i>Discord</h3>
            </div>
            <div class="card-body" style="font-size:12px;">
                <div><span class="text-muted">Tác giả:</span> {{ $article->discord_author ?? '—' }}</div>
                <div class="mt-1"><span class="text-muted">Message ID:</span><br>
                    <code style="font-size:10px;">{{ $article->discord_message_id }}</code>
                </div>
            </div>
        </div>
        @endif

        <!-- Delete -->
        <div class="card card-outline card-danger">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.library.destroy', $article) }}"
                      onsubmit="return confirm('Xoá bài viết này?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm btn-block">
                        <i class="fas fa-trash mr-1"></i>Xoá bài viết
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
