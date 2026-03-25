@extends('layouts.admin')

@section('title', __('messages.poker_lobby'))

@section('content')
<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('entertainment.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('messages.entertainment_hall') }}
        </a>
        <button data-toggle="modal" data-target="#create-table-modal" class="btn btn-success float-right ml-2">
            <i class="fas fa-plus"></i> {{ __('messages.create_table') }}
        </button>
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
                                <button class="btn btn-primary btn-sm btn-join-table"
                                    data-join-url="{{ route('entertainment.poker.join', $table) }}"
                                    data-room-url="{{ route('entertainment.poker.show', $table) }}">
                                    <i class="fas fa-chair"></i>
                                    {{ __('messages.join_table') }}
                                </button>
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
{{-- Create Table Modal --}}
<div class="modal fade" id="create-table-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus mr-1"></i> {{ __('messages.create_table') }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="create-table-errors" class="alert alert-danger" style="display:none"></div>
                <form id="create-table-form">
                    <div class="form-group">
                        <label>{{ __('messages.table_name') }}</label>
                        <input type="text" class="form-control" name="name" placeholder="My Table" required maxlength="60">
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.table_type') }}</label>
                        <select class="form-control" name="type">
                            <option value="no_limit_holdem">{{ __('messages.no_limit_holdem') }}</option>
                            <option value="pot_limit_omaha">{{ __('messages.pot_limit_omaha') }}</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>{{ __('messages.small_blind') }}</label>
                            <input type="number" class="form-control" name="small_blind" value="100" min="1" required>
                        </div>
                        <div class="form-group col-6">
                            <label>{{ __('messages.big_blind') }}</label>
                            <input type="number" class="form-control" name="big_blind"   value="200" min="2" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>{{ __('messages.min_buy_in') }}</label>
                            <input type="number" class="form-control" name="min_buy_in"  value="2000"  min="1" required>
                        </div>
                        <div class="form-group col-6">
                            <label>{{ __('messages.max_buy_in') }}</label>
                            <input type="number" class="form-control" name="max_buy_in"  value="20000" min="1" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.max_players') }}</label>
                        <select class="form-control" name="max_players">
                            @foreach([2,3,4,5,6,7,8,9] as $n)
                                <option value="{{ $n }}" {{ $n == 6 ? 'selected' : '' }}>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                <button type="button" class="btn btn-success" id="create-table-submit">
                    <i class="fas fa-plus"></i> {{ __('messages.create_table') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Full-screen loading overlay shown while joining a table --}}
<div id="join-loading-overlay" style="
    display: none;
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.65);
    flex-direction: column;
    align-items: center; justify-content: center;
    color: #fff; text-align: center;">
    <div class="spinner-border text-light mb-3" role="status" style="width:3rem;height:3rem;"></div>
    <h5 class="mb-1">{{ __('messages.joining_table') }}</h5>
    <small class="text-white-50">{{ __('messages.joining_table_hint') }}</small>
</div>
@endsection

@push('scripts')
<script type="module">
    $(document).ready(function() {
        console.log('Poker Lobby Loaded');

        var joinTimeout = null;

        function showLoading() {
            $('#join-loading-overlay').css('display', 'flex');
            // Auto-hide after 5 minutes as safety fallback
            joinTimeout = setTimeout(hideLoading, 5 * 60 * 1000);
        }

        function hideLoading() {
            clearTimeout(joinTimeout);
            $('#join-loading-overlay').hide();
        }

        // Join table via AJAX - avoids form-inside-table HTML issues
        $(document).on('click', '.btn-join-table', function() {
            const btn     = $(this).prop('disabled', true);
            const joinUrl = btn.data('join-url');
            const roomUrl = btn.data('room-url');

            showLoading();

            $.ajax({
                url: joinUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    // Keep overlay visible until navigation completes
                    window.location.href = res.redirect || roomUrl;
                },
                error: function(xhr) {
                    hideLoading();
                    btn.prop('disabled', false);
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to join table.';
                    alert(msg);
                }
            });
        });

        // Create table - AJAX submit
        $('#create-table-submit').click(function() {
            var $btn = $(this).prop('disabled', true);
            var $err = $('#create-table-errors').hide();

            $.ajax({
                url: "{{ route('entertainment.poker.create') }}",
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: $('#create-table-form').serialize(),
                success: function(res) {
                    showLoading();
                    $('#create-table-modal').modal('hide');
                    window.location.href = res.redirect;
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    var errors = xhr.responseJSON && xhr.responseJSON.errors
                        ? Object.values(xhr.responseJSON.errors).flat().join('<br>')
                        : (xhr.responseJSON && xhr.responseJSON.message) || 'Error creating table.';
                    $err.html(errors).show();
                }
            });
        });

        // Re-enable submit button when modal opens
        $('#create-table-modal').on('show.bs.modal', function() {
            $('#create-table-submit').prop('disabled', false);
            $('#create-table-errors').hide();
        });

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

        // WebSocket Listener - initialize connection now that we're in the lobby
        if (typeof window.initEcho === 'function') {
            window.initEcho();
        }

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
@endpush
