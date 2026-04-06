@extends('layouts.admin')

@section('title', __('messages.coin_history_title') . ': ' . $user->account)

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">
            <i class="fas fa-history mr-2" style="color:#f6c23e;"></i>
            {{ __('messages.coin_history_title') }} &mdash; <strong>{{ $user->account }}</strong>
            <span class="badge badge-warning ml-2">{{ number_format($user->z_coins) }} Zoo</span>
        </h3>
        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> {{ __('messages.coin_history_back') }}
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead class="thead-dark">
                <tr>
                    <th style="width:160px;">{{ __('messages.coin_col_time') }}</th>
                    <th style="width:110px;">{{ __('messages.coin_col_type') }}</th>
                    <th class="text-right" style="width:130px;">{{ __('messages.coin_col_amount') }}</th>
                    <th class="text-right" style="width:130px;">{{ __('messages.coin_col_before') }}</th>
                    <th class="text-right" style="width:130px;">{{ __('messages.coin_col_after') }}</th>
                    <th>{{ __('messages.coin_col_note') }}</th>
                    <th style="width:120px;">{{ __('messages.coin_col_by') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $tx)
                <tr>
                    <td class="text-muted small">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge {{ $tx->typeBadgeClass() }}">{{ $tx->typeLabel() }}</span>
                    </td>
                    <td class="text-right font-weight-bold
                        {{ in_array($tx->type, ['add','transfer_in','daily_bonus']) ? 'text-success' : 'text-danger' }}">
                        {{ in_array($tx->type, ['add','transfer_in','daily_bonus']) ? '+' : '-' }}{{ number_format($tx->amount) }}
                    </td>
                    <td class="text-right text-muted small">{{ number_format($tx->balance_before) }}</td>
                    <td class="text-right small">{{ number_format($tx->balance_after) }}</td>
                    <td>
                        <span class="small">{{ $tx->note }}</span>
                        @if($tx->relatedUser)
                            <span class="badge badge-secondary ml-1">{{ $tx->relatedUser->account }}</span>
                        @endif
                    </td>
                    <td class="small text-muted">
                        @if($tx->staff)
                            <i class="fas fa-user-shield mr-1"></i>{{ $tx->staff->account }}
                        @elseif($tx->type === 'daily_bonus')
                            <i class="fas fa-robot mr-1"></i>{{ __('messages.coin_system') }}
                        @elseif(in_array($tx->type, ['transfer_in','transfer_out']))
                            <i class="fas fa-exchange-alt mr-1"></i>User
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">{{ __('messages.coin_no_transactions') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer">
        {{ $transactions->links() }}
    </div>
    @endif
</div>
@endsection
