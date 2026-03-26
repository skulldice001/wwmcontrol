@extends('layouts.admin')

@section('title', __('messages.zcoin_history_title'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2" style="color:#f6c23e;"></i>
                    {{ __('messages.zcoin_history_title') }}
                </h3>
                <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> {{ __('messages.back') }}
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width:160px;">{{ __('messages.zcoin_tx_time') }}</th>
                            <th style="width:110px;">{{ __('messages.zcoin_tx_type') }}</th>
                            <th class="text-right" style="width:130px;">{{ __('messages.zcoin_tx_amount') }}</th>
                            <th class="text-right" style="width:130px;">{{ __('messages.zcoin_tx_before') }}</th>
                            <th class="text-right" style="width:130px;">{{ __('messages.zcoin_tx_after') }}</th>
                            <th>{{ __('messages.zcoin_tx_note') }}</th>
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
                                {{ in_array($tx->type, ['add','transfer_in','daily_bonus']) ? '+' : '-' }}{{ number_format($tx->amount) }} Zoo
                            </td>
                            <td class="text-right text-muted small">{{ number_format($tx->balance_before) }}</td>
                            <td class="text-right small">{{ number_format($tx->balance_after) }}</td>
                            <td class="small">
                                {{ $tx->note }}
                                @if($tx->relatedUser)
                                    @if($tx->type === 'transfer_out')
                                        → <span class="badge badge-secondary">{{ $tx->relatedUser->account }}</span>
                                    @else
                                        ← <span class="badge badge-secondary">{{ $tx->relatedUser->account }}</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">{{ __('messages.zcoin_no_transactions') }}</td>
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
    </div>
</div>
@endsection
