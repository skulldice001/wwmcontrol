@extends('layouts.admin')
@section('title', 'Tạo Sự Kiện Quay Số')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-dice mr-2"></i>Tạo Sự Kiện Quay Số Ngẫu Nhiên</h3>
    </div>
    <form action="{{ route('admin.events.lucky_draw.store') }}" method="POST" id="luckyDrawForm">
        @csrf
        <div class="card-body">

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-group">
                <label>Tên sự kiện <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required
                       placeholder="VD: Quay số mừng sinh nhật Guild"
                       value="{{ old('title') }}">
            </div>

            <div class="form-group">
                <label>Mô tả</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Mô tả ngắn về sự kiện...">{{ old('description') }}</textarea>
            </div>

            <div class="form-group">
                <label>Thời gian quay số <span class="text-danger">*</span></label>
                <input type="datetime-local" name="draw_at" class="form-control" required
                       value="{{ old('draw_at') }}">
                <small class="text-muted">Hệ thống sẽ tự động quay khi đến giờ. Staff cũng có thể quay thủ công.</small>
            </div>

            <hr>

            <div class="d-flex align-items-center mb-3">
                <h5 class="mb-0">Danh sách giải thưởng</h5>
                <button type="button" class="btn btn-sm btn-success ml-3" id="addPrize">
                    <i class="fas fa-plus"></i> Thêm giải
                </button>
            </div>
            <small class="text-muted d-block mb-3">
                Mỗi giải sẽ quay 1 người trúng. Người trúng không trùng nhau. Thứ tự = thứ hạng giải.
            </small>

            <div id="prizeList">
                {{-- Initial prize row --}}
                <div class="prize-row card card-outline card-secondary mb-3" data-index="0">
                    <div class="card-body py-2">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="badge badge-warning prize-rank">Giải 1</span>
                            </div>
                            <div class="col">
                                <input type="text" name="prizes[0][name]" class="form-control form-control-sm"
                                       placeholder="Tên giải (VD: Giải nhất)" required
                                       value="{{ old('prizes.0.name') }}">
                            </div>
                            <div class="col">
                                <input type="text" name="prizes[0][description]" class="form-control form-control-sm"
                                       placeholder="Mô tả phần thưởng"
                                       value="{{ old('prizes.0.description') }}">
                            </div>
                            <div class="col-3">
                                <div class="input-group input-group-sm">
                                    <input type="number" name="prizes[0][zoo_coin_amount]" class="form-control form-control-sm"
                                           placeholder="Zoo Coins" min="0" value="{{ old('prizes.0.zoo_coin_amount', 0) }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">🪙</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-danger btn-sm remove-prize" style="display:none">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Tạo sự kiện
            </button>
            <a href="{{ route('admin.events.index') }}" class="btn btn-secondary ml-2">Hủy</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script type="module">
let prizeCount = 1;

function updateRanks() {
    $('#prizeList .prize-row').each(function(i) {
        $(this).attr('data-index', i);
        $(this).find('.prize-rank').text('Giải ' + (i + 1));
        $(this).find('input').each(function() {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/prizes\[\d+\]/, 'prizes[' + i + ']'));
            }
        });
        const removeBtn = $(this).find('.remove-prize');
        removeBtn.toggle($('#prizeList .prize-row').length > 1);
    });
}

$('#addPrize').on('click', function() {
    const idx = prizeCount++;
    const row = `
        <div class="prize-row card card-outline card-secondary mb-3" data-index="${idx}">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="badge badge-warning prize-rank">Giải ${$('#prizeList .prize-row').length + 1}</span>
                    </div>
                    <div class="col">
                        <input type="text" name="prizes[${idx}][name]" class="form-control form-control-sm"
                               placeholder="Tên giải" required>
                    </div>
                    <div class="col">
                        <input type="text" name="prizes[${idx}][description]" class="form-control form-control-sm"
                               placeholder="Mô tả phần thưởng">
                    </div>
                    <div class="col-3">
                        <div class="input-group input-group-sm">
                            <input type="number" name="prizes[${idx}][zoo_coin_amount]" class="form-control form-control-sm"
                                   placeholder="Zoo Coins" min="0" value="0">
                            <div class="input-group-append"><span class="input-group-text">🪙</span></div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-danger btn-sm remove-prize">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;
    $('#prizeList').append(row);
    updateRanks();
});

$(document).on('click', '.remove-prize', function() {
    $(this).closest('.prize-row').remove();
    updateRanks();
});
</script>
@endpush
