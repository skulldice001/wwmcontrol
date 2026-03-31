@extends('layouts.admin')

@section('title', 'Tiến Lên')

@section('content')
@include('partials.notify')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="{{ route('entertainment.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Sảnh giải trí
        </a>
        <h5 class="d-inline ml-3 mb-0">🃏 Tiến Lên</h5>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-warning mr-2" data-toggle="modal" data-target="#createAiModal">
            <i class="fas fa-robot"></i> Chơi vs AI
        </button>
        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createModal">
            <i class="fas fa-plus"></i> Tạo phòng
        </button>
    </div>
</div>

<div class="row">
    @forelse($tables as $table)
    <div class="col-md-4 col-sm-6 mb-3">
        <div class="card bg-dark text-light border-secondary">
            <div class="card-body">
                <h6 class="card-title mb-1">
                    {{ $table->name }}
                    @if($table->is_ai_mode)
                        <span class="badge badge-warning ml-1">vs AI</span>
                    @endif
                    <span class="badge badge-info ml-1">{{ $table->variant === 'mien_nam' ? 'Miền Nam' : 'Miền Bắc' }}</span>
                </h6>
                <div class="text-muted small mb-2">
                    <i class="fas fa-coins mr-1"></i> Phí: <strong class="text-warning">{{ number_format($table->entry_fee) }} Zoo</strong>
                    &nbsp;·&nbsp;
                    <i class="fas fa-users mr-1"></i> {{ $table->players->count() }}/4 người
                </div>
                <span class="badge {{ $table->status === 'waiting' ? 'badge-success' : 'badge-secondary' }}">
                    {{ $table->status === 'waiting' ? 'Đang chờ' : ($table->status === 'playing' ? 'Đang chơi' : 'Đã kết thúc') }}
                </span>
                <div class="mt-2">
                    <a href="{{ route('entertainment.tienlen.room', $table->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Vào phòng
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="text-center text-muted py-5">
            <i class="fas fa-table fa-3x mb-2"></i>
            <p>Chưa có phòng nào. Hãy tạo phòng mới hoặc thử chơi vs AI!</p>
        </div>
    </div>
    @endforelse
</div>

<!-- Create Room Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Tạo phòng Tiến Lên</h5>
                <button type="button" class="close text-light" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('entertainment.tienlen.create') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="small">Tên phòng</label>
                        <input type="text" name="name" class="form-control form-control-sm bg-dark text-light border-secondary"
                               value="{{ Auth::user()->name }}'s room" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="small">Luật chơi</label>
                        <select name="variant" class="form-control form-control-sm bg-dark text-light border-secondary">
                            <option value="mien_nam">Miền Nam (có chặt 2)</option>
                            <option value="mien_bac">Miền Bắc (không chặt)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small">Phí tham gia (Zoo)</label>
                        <input type="number" name="entry_fee" class="form-control form-control-sm bg-dark text-light border-secondary"
                               value="100" min="10" max="10000">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-primary">Tạo phòng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Play vs AI Modal -->
<div class="modal fade" id="createAiModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title"><i class="fas fa-robot mr-1"></i> Chơi vs AI</h5>
                <button type="button" class="close text-light" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('entertainment.tienlen.ai') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted">Phí 100 Zoo/ván · Thắng ÷10 · Tối đa 5000 Zoo/ngày</p>
                    <div class="form-group">
                        <label class="small">Luật chơi</label>
                        <select name="variant" class="form-control form-control-sm bg-dark text-light border-secondary">
                            <option value="mien_nam">Miền Nam (có chặt 2)</option>
                            <option value="mien_bac">Miền Bắc (không chặt)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="fas fa-play mr-1"></i> Bắt đầu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
