@extends('layouts.admin')

@section('title', __('messages.poker_lobby'))

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('entertainment.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.entertainment_hall') }}
        </a>
        <button id="test-update-btn" class="btn btn-outline-info float-right">
            <i class="fas fa-sync"></i> Test Update
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.poker_lobby') }}</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped projects">
                <thead>
                    <tr>
                        <th style="width: 20%">
                            {{ __('messages.table_name') }}
                        </th>
                        <th style="width: 20%">
                            {{ __('messages.blinds') }}
                        </th>
                        <th style="width: 20%">
                            {{ __('messages.buy_in') }}
                        </th>
                        <th style="width: 15%">
                            {{ __('messages.players') }}
                        </th>
                        <th style="width: 10%" class="text-center">
                            {{ __('messages.status') }}
                        </th>
                        <th style="width: 15%">
                        </th>
                    </tr>
                </thead>
                <tbody id="poker-table-body">
                    @foreach($tables as $table)
                    <tr>
                        <td>
                            <a>
                                {{ $table->name }}
                            </a>
                            <br/>
                            <small>
                                {{ __('messages.type') }}: {{ $table->type }}
                            </small>
                        </td>
                        <td>
                            <span class="badge badge-info">{{ number_format($table->small_blind) }} / {{ number_format($table->big_blind) }}</span>
                        </td>
                        <td>
                            {{ number_format($table->min_buy_in) }} - {{ number_format($table->max_buy_in) }}
                        </td>
                        <td class="project_progress">
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-green" role="progressbar" aria-valuenow="{{ $table->current_players }}" aria-valuemin="0" aria-valuemax="{{ $table->max_players }}" style="width: {{ ($table->current_players / $table->max_players) * 100 }}%">
                                </div>
                            </div>
                            <small>
                                {{ $table->current_players }} / {{ $table->max_players }}
                            </small>
                        </td>
                        <td class="project-state">
                            @if($table->status == 'waiting')
                                <span class="badge badge-success">{{ __('messages.waiting') }}</span>
                            @elseif($table->status == 'playing')
                                <span class="badge badge-warning">{{ __('messages.playing') }}</span>
                            @else
                                <span class="badge badge-danger">{{ $table->status }}</span>
                            @endif
                        </td>
                        <td class="project-actions text-right">
                            @if($table->current_players < $table->max_players)
                                <a class="btn btn-primary btn-sm" href="#">
                                    <i class="fas fa-chair"></i>
                                    {{ __('messages.join_table') }}
                                </a>
                            @else
                                <button class="btn btn-secondary btn-sm" disabled>
                                    <i class="fas fa-ban"></i>
                                    {{ __('messages.table_full') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script type="module">
    $(document).ready(function() {
        console.log('Poker Lobby Loaded');

        $('#test-update-btn').click(function() {
            $.get("{{ route('entertainment.poker.test-update') }}", function(data) {
                console.log('Test update triggered:', data);
                if (!window.Echo) {
                    refreshTables(); // Manually refresh if no socket
                }
            });
        });

        // Function to refresh table list
        function refreshTables() {
            $.get("{{ route('entertainment.poker') }}", function(data) {
                var newBody = $(data).find('#poker-table-body').html();
                $('#poker-table-body').html(newBody);
            }).fail(function() {
                console.log('Error refreshing tables');
            });
        }

        // WebSocket Listener (if Echo is available)
        if (window.Echo) {
            console.log('Subscribing to poker.lobby channel...');
            window.Echo.channel('poker.lobby')
                .listen('PokerTableUpdated', (e) => {
                    console.log('Poker Table Updated:', e.table);
                    refreshTables();
                });
        } else {
            console.log('Echo not available');
        }

        // Fallback Polling (every 5 seconds)
        // setInterval(refreshTables, 5000);
    });
</script>
@endsection
