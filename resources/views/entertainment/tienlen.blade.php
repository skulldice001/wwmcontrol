@extends('layouts.admin')

@section('title', __('messages.tienlen_title'))

@section('content')
@include('partials.notify')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="{{ route('entertainment.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.tienlen_lobby_title') }}
        </a>
        <h5 class="d-inline ml-3 mb-0">{{ __('messages.tienlen_title') }}</h5>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-warning mr-2" data-toggle="modal" data-target="#createAiModal">
            <i class="fas fa-robot"></i> {{ __('messages.tienlen_vs_ai') }}
        </button>
        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createModal">
            <i class="fas fa-plus"></i> {{ __('messages.tienlen_create') }}
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
                    <span class="badge badge-info ml-1">{{ $table->variant === 'mien_nam' ? __('messages.tienlen_south') : __('messages.tienlen_north') }}</span>
                </h6>
                <div class="text-muted small mb-2">
                    <i class="fas fa-coins mr-1"></i> {{ __('messages.tienlen_fee') }} <strong class="text-warning">{{ number_format($table->entry_fee) }} Zoo</strong>
                    &nbsp;·&nbsp;
                    <i class="fas fa-users mr-1"></i> {{ $table->players->count() }}/4 {{ __('messages.tienlen_players') }}
                </div>
                <span class="badge {{ $table->status === 'waiting' ? 'badge-success' : 'badge-secondary' }}">
                    {{ $table->status === 'waiting' ? __('messages.tienlen_waiting') : ($table->status === 'playing' ? __('messages.tienlen_playing') : __('messages.tienlen_ended')) }}
                </span>
                <div class="mt-2">
                    <a href="{{ route('entertainment.tienlen.room', $table->id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-sign-in-alt"></i> {{ __('messages.tienlen_enter') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="text-center text-muted py-5">
            <i class="fas fa-table fa-3x mb-2"></i>
            <p>{{ __('messages.tienlen_no_rooms') }}</p>
        </div>
    </div>
    @endforelse
</div>

<!-- Create Room Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">{{ __('messages.tienlen_create_title') }}</h5>
                <button type="button" class="close text-light" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('entertainment.tienlen.create') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="small">{{ __('messages.tienlen_room_name') }}</label>
                        <input type="text" name="name" class="form-control form-control-sm bg-dark text-light border-secondary"
                               value="{{ Auth::user()->name }}'s room" required maxlength="50">
                    </div>
                    <div class="form-group">
                        <label class="small">{{ __('messages.tienlen_rules') }}</label>
                        <select name="variant" class="form-control form-control-sm bg-dark text-light border-secondary">
                            <option value="mien_nam">{{ __('messages.tienlen_south') }}</option>
                            <option value="mien_bac">{{ __('messages.tienlen_north') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small">{{ __('messages.tienlen_entry_fee') }}</label>
                        <input type="number" name="entry_fee" class="form-control form-control-sm bg-dark text-light border-secondary"
                               value="100" min="10" max="10000">
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('messages.tienlen_start') }}</button>
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
                <h5 class="modal-title"><i class="fas fa-robot mr-1"></i> {{ __('messages.tienlen_ai_title') }}</h5>
                <button type="button" class="close text-light" data-dismiss="modal">&times;</button>
            </div>
            <form action="{{ route('entertainment.tienlen.ai') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted">{{ __('messages.tienlen_ai_desc') }}</p>
                    <div class="form-group">
                        <label class="small">{{ __('messages.tienlen_rules') }}</label>
                        <select name="variant" class="form-control form-control-sm bg-dark text-light border-secondary">
                            <option value="mien_nam">{{ __('messages.tienlen_south') }}</option>
                            <option value="mien_bac">{{ __('messages.tienlen_north') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="fas fa-play mr-1"></i> {{ __('messages.tienlen_start') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
