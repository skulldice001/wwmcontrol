@extends('layouts.admin')

@section('title', __('messages.available_events'))

@section('content')
<div class="row">
    @if($events->isEmpty())
        <div class="col-12">
            <div class="alert alert-info">
                {{ __('messages.no_upcoming_events') }}
            </div>
        </div>
    @endif

    @foreach($events as $event)
    <div class="col-md-6 col-lg-4">
        <div class="card card-{{ $event->type == 'guild_war' ? 'danger' : 'primary' }} card-outline">
            <div class="card-header">
                <h5 class="card-title m-0">{{ $event->title }}</h5>
                <div class="card-tools">
                    <span class="badge badge-{{ $event->status == 'ongoing' ? 'success' : 'warning' }}">
                        {{ ucfirst($event->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <p class="card-text">{{ Str::limit($event->description, 100) }}</p>
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>{{ __('messages.start_time') }}</b>
                        <span class="float-right">{{ \Carbon\Carbon::parse($event->start_time)->format('Y-m-d H:i') }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{{ __('messages.end_time') }}</b>
                        <span class="float-right">
                            {{ $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('Y-m-d H:i') : __('messages.not_available') }}
                        </span>
                    </li>
                    @if($event->location)
                    <li class="list-group-item">
                        <b>{{ __('messages.location') }}</b>
                        <span class="float-right">{{ $event->location }}</span>
                    </li>
                    @endif
                </ul>

                @if($event->is_registered)
                    <div class="alert alert-success">
                        <i class="fas fa-check"></i>
                        {{ __('messages.you_joined_event') }}
                        @if($event->preferred_time)
                            <div class="mt-1" style="font-size: 1rem;">
                                {{ __('messages.preferred_time_label') }}
                                {{ $event->preferred_time }}
                            </div>
                        @endif
                        @if($event->type == 'guild_war')
                            <div class="mt-1 d-flex justify-content-between align-items-center" style="font-size: 1rem;">
                                @if(!empty($event->team_name))
                                    <span>
                                        {{ __('messages.team_participating') }} {{ $event->team_name }}
                                        <button type="button"
                                                class="btn btn-link p-0 ml-1"
                                                onclick="showTeamMission({{ json_encode($event->team_mission) }})"
                                                title="{{ __('messages.view_team_mission') }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </span>
                                    @if(!empty($event->team_captain_name))
                                        <span><strong>{{ __('messages.team_captain') }} {{ $event->team_captain_name }}</strong></span>
                                    @endif
                                @else
                                    <span>
                                        {{ __('messages.status_waiting_formation') }}
                                        <button type="button"
                                                class="btn btn-link p-0 ml-1"
                                                onclick="showTeamMission(null)"
                                                title="{{ __('messages.view_team_mission') }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </span>
                                @endif
                            </div>
                            @if($event->is_placed)
                                <div class="mt-2">
                                    <a href="{{ route('events.map', $event->id) }}" class="btn btn-success btn-block">
                                        <i class="fas fa-map-marked-alt mr-2"></i> Vị trí xuất phát
                                    </a>
                                </div>
                            @endif
                        @endif
                    </div>
                    @if($event->status == 'upcoming')
                        <form action="{{ route('events.unregister', $event->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('{{ __('messages.leave_event_question') }}')">
                                {{ __('messages.leave_event') }}
                            </button>
                        </form>
                    @endif
                @else
                    @if($event->type == 'guild_war')
                        <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#joinGuildWarModal{{ $event->id }}">
                            {{ __('messages.join_guild_war') }}
                        </button>

                        <!-- Modal -->
                        <div class="modal fade" id="joinGuildWarModal{{ $event->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('events.register', $event->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                {{ __('messages.join_guild_war') }}
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>{{ __('messages.preferred_time_required') }}</label>
                                                <input type="text" name="preferred_time" class="form-control" placeholder="{{ __('messages.preferred_time_placeholder') }}" required>
                                                <small class="form-text text-muted">
                                                    {{ __('messages.preferred_time_help') }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                {{ __('messages.close') }}
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('messages.join') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <form action="{{ route('events.register', $event->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block">
                                {{ __('messages.join_event') }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="modal fade" id="teamMissionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('messages.team_mission_description') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="teamMissionContent" style="white-space: pre-line;"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    window.showTeamMission = function(description) {
        var message = description;
        if (!message) {
            message = @json(__('messages.no_team_mission'));
        }

        var contentEl = document.getElementById('teamMissionContent');
        if (contentEl) {
            contentEl.textContent = message;
        }

        if (typeof $ !== 'undefined' && $('#teamMissionModal').modal) {
            $('#teamMissionModal').modal('show');
        } else {
            alert(message);
        }
    }
</script>
@endpush
