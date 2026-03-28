@extends('layouts.admin')
@section('title', 'Bingo Zoo')
@section('content')
@include('partials.notify')

<div style="max-width:900px;margin:0 auto;padding:0 16px 40px;">

  {{-- Header --}}
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
    <a href="{{ route('entertainment.index') }}" class="btn btn-sm btn-secondary">
      <i class="fas fa-arrow-left mr-1"></i> Sảnh
    </a>
    <h2 style="margin:0;font-size:22px;font-weight:900;color:#fff;">
      <i class="fas fa-th mr-2" style="color:#f6c23e;"></i>Bingo Zoo
    </h2>
    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#createModal">
      <i class="fas fa-plus mr-1"></i> Tạo bàn
    </button>
  </div>

  {{-- Table list --}}
  <div class="row">
    @forelse($tables as $table)
    <div class="col-md-4 mb-3">
      <div style="background:linear-gradient(145deg,#1a0a2e,#3b1068);border-radius:14px;padding:18px;box-shadow:0 4px 20px rgba(0,0,0,.4);">
        <div style="font-size:15px;font-weight:800;color:#fff;margin-bottom:6px;">{{ $table->name }}</div>
        <div style="font-size:12px;color:rgba(255,255,255,.5);margin-bottom:12px;">
          Phí: <span style="color:#f6c23e;font-weight:700;">{{ number_format($table->entry_fee) }} Zoo</span>
          &nbsp;·&nbsp;
          <span style="{{ $table->status === 'playing' ? 'color:#e74c3c;' : 'color:#2ecc71;' }}">
            {{ $table->status === 'playing' ? 'Đang chơi' : 'Chờ' }}
          </span>
        </div>
        <div style="font-size:12px;color:rgba(255,255,255,.4);margin-bottom:14px;">
          {{ $table->current_players }}/{{ $table->max_players }} người
          &nbsp;·&nbsp; Cần {{ $table->min_players }} để bắt đầu
        </div>
        @if($table->status === 'waiting' && $table->current_players < $table->max_players && !$table->players->contains('id', $userId))
        <form action="{{ route('entertainment.bingo.join', $table) }}" method="POST">
          @csrf
          <button class="btn btn-warning btn-sm w-100">Tham gia</button>
        </form>
        @elseif($table->players->contains('id', $userId))
        <a href="{{ route('entertainment.bingo.show', $table) }}" class="btn btn-info btn-sm w-100">Vào phòng</a>
        @else
        <button class="btn btn-secondary btn-sm w-100" disabled>{{ $table->status === 'playing' ? 'Đang chơi' : 'Đầy' }}</button>
        @endif
      </div>
    </div>
    @empty
    <div class="col-12 text-center text-muted py-5">Chưa có bàn nào. Hãy tạo bàn mới!</div>
    @endforelse
  </div>

</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content" style="background:#1e1e2e;border:1px solid rgba(255,255,255,.1);">
      <div class="modal-header" style="border-color:rgba(255,255,255,.1);">
        <h5 class="modal-title text-white">Tạo bàn Bingo</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <form action="{{ route('entertainment.bingo.create') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="text-white" style="font-size:12px;">Tên bàn</label>
            <input type="text" name="name" class="form-control form-control-sm" style="background:#2a2a3e;color:#fff;border-color:rgba(255,255,255,.2);" required maxlength="60" placeholder="Bàn Bingo #1">
          </div>
          <div class="form-group mb-0">
            <label class="text-white" style="font-size:12px;">Phí vào (Zoo)</label>
            <select name="entry_fee" class="form-control form-control-sm" style="background:#2a2a3e;color:#fff;border-color:rgba(255,255,255,.2);">
              <option value="10">10 Zoo</option>
              <option value="50" selected>50 Zoo</option>
              <option value="100">100 Zoo</option>
              <option value="500">500 Zoo</option>
            </select>
          </div>
        </div>
        <div class="modal-footer" style="border-color:rgba(255,255,255,.1);">
          <button type="submit" class="btn btn-warning btn-sm w-100">Tạo &amp; Vào phòng</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
