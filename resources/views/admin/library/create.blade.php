@extends('layouts.admin')
@section('title', 'Tạo bài viết')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
@endpush

@section('content')
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus mr-1"></i>Tạo bài viết mới</h3>
        <div class="card-tools">
            <a href="{{ route('admin.library.index') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i>Quay lại
            </a>
        </div>
    </div>
    <form method="POST" action="{{ route('admin.library.store') }}">
        @csrf
        <div class="card-body">
            @include('admin.library._form')
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save mr-1"></i>Lưu bản nháp
            </button>
        </div>
    </form>
</div>
@endsection
