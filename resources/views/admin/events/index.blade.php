@extends('layouts.admin')

@section('title', __('messages.events_management_title'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.events_list') }}</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createEventModal">
                {{ __('messages.create_new_event') }}
            </button>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>{{ __('messages.event_title') }}</th>
                    <th>{{ __('messages.event_type') }}</th>
                    <th>{{ __('messages.event_status') }}</th>
                    <th>{{ __('messages.event_start_time') }}</th>
                    <th>{{ __('messages.event_created_by') }}</th>
                    <th>{{ __('messages.participants_count') }}</th>
                    <th>{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                <tr>
                    <td>{{ $event->title }}</td>
                    <td>
                        @if($event->type === 'guild_war')
                            <span class="badge badge-danger">{{ __('messages.event_guild_war') }}</span>
                        @elseif($event->type === 'lucky_draw')
                            <span class="badge badge-purple" style="background:#6f42c1">🎲 Quay Số</span>
                        @else
                            <span class="badge badge-success">{{ __('messages.event_casual') }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $event->status == 'ongoing' ? 'success' : ($event->status == 'upcoming' ? 'warning' : 'secondary') }}">
                            @switch($event->status)
                                @case('upcoming')
                                    {{ __('messages.event_status_upcoming') }}
                                    @break
                                @case('ongoing')
                                    {{ __('messages.event_status_ongoing') }}
                                    @break
                                @case('completed')
                                    {{ __('messages.event_status_completed') }}
                                    @break
                                @case('cancelled')
                                    {{ __('messages.event_status_cancelled') }}
                                    @break
                                @default
                                    {{ $event->status }}
                            @endswitch
                        </span>
                    </td>
                    <td>
                        <div>{{ \Carbon\Carbon::parse($event->start_time)->format('H:i d/m/Y') }}</div>
                        <small class="text-muted">
                            {{ \Carbon\Carbon::parse($event->start_time)->locale('vi')->isoFormat('dddd') }}
                        </small>
                    </td>
                    <td>{{ $event->creator ? $event->creator->name : __('messages.not_available') }}</td>
                    <td>{{ $event->participants_count }}</td>
                    <td>
                        <a href="{{ route('admin.events.participants', $event->id) }}" class="btn btn-primary btn-sm mr-1">
                            <i class="fas fa-users"></i> {{ __('messages.participants') ?? 'Participants' }}
                        </a>
                        @if($event->type == 'guild_war' && !in_array($event->status, ['completed', 'cancelled']))
                            <a href="{{ route('admin.events.formation', $event->id) }}" class="btn btn-warning btn-sm mr-1">
                                <i class="fas fa-users-cog"></i> {{ __('messages.sort_formation') }}
                            </a>
                        @endif
                        @if($event->type === 'lucky_draw')
                            @if(!empty($event->lucky_draw_data['drawn_at']))
                                <a href="{{ route('admin.events.lucky_draw_result', $event->id) }}" class="btn btn-warning btn-sm mr-1">
                                    <i class="fas fa-trophy"></i> Kết quả
                                </a>
                            @elseif(!in_array($event->status, ['completed','cancelled']))
                                <a href="{{ route('admin.events.lucky_draw_result', $event->id) }}" class="btn btn-info btn-sm mr-1">
                                    <i class="fas fa-dice"></i> Quay số
                                </a>
                            @endif
                        @endif
                        @if(!in_array($event->status, ['completed', 'cancelled']))
                            <button type="button"
                                    class="btn btn-success btn-sm mr-1 btn-complete-event"
                                    data-toggle="modal"
                                    data-target="#confirmCompleteModal"
                                    data-action="{{ route('admin.events.complete', $event->id) }}"
                                    data-title="{{ $event->title }}">
                                <i class="fas fa-flag-checkered"></i> {{ __('messages.event_end') }}
                            </button>
                            <a href="{{ route('admin.events.edit', $event->id) }}" class="btn btn-info btn-sm mr-1">
                                <i class="fas fa-pencil-alt"></i> {{ __('messages.edit') }}
                            </a>
                            <form action="{{ route('admin.events.destroy', $event->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.are_you_sure') }}')">
                                    <i class="fas fa-trash"></i> {{ __('messages.delete') }}
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="confirmCompleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmCompleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" id="completeEventForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmCompleteModalLabel">{{ __('messages.event_end_confirm_title') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>{{ __('messages.event_end_confirm_question') }}</p>
                    <p class="font-weight-bold event-title mb-0"></p>
                    <p class="mb-0 text-muted">{{ __('messages.event_end_confirm_note') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('messages.confirm') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1" role="dialog" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">{{ __('messages.select_event_type') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <p>{{ __('messages.select_event_type_help') }}</p>
                <div class="row">
                    <div class="col-4">
                        <a href="{{ route('admin.events.create', ['type' => 'casual']) }}" class="btn btn-success btn-block btn-lg p-4">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i><br>
                            {{ __('messages.casual_event') }}
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="{{ route('admin.events.create', ['type' => 'guild_war']) }}" class="btn btn-danger btn-block btn-lg p-4">
                            <i class="fas fa-khanda fa-2x mb-2"></i><br>
                            {{ __('messages.guild_war_event') }}
                        </a>
                    </div>
                    <div class="col-4">
                        <a href="{{ route('admin.events.create', ['type' => 'lucky_draw']) }}" class="btn btn-block btn-lg p-4" style="background:#6f42c1;color:#fff">
                            <i class="fas fa-dice fa-2x mb-2"></i><br>
                            Quay Số
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    $(function () {
        $('#confirmCompleteModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var action = button.data('action');
            var title = button.data('title');
            var modal = $(this);
            modal.find('#completeEventForm').attr('action', action);
            modal.find('.event-title').text(title);
        });
    });
</script>
@endpush
