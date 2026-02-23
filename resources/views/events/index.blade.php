@extends('layouts.admin')

@section('title', __('messages.available_events'))

@section('content')
<div class="row">
    @if($events->isEmpty())
        <div class="col-12">
            <div class="alert alert-info">
                {{ app()->getLocale() === 'vi' ? 'Hiện không có sự kiện sắp diễn ra hoặc đang diễn ra.' : 'No upcoming or ongoing events at the moment.' }}
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
                        <b>{{ app()->getLocale() === 'vi' ? 'Thời gian bắt đầu' : 'Start Time' }}</b>
                        <span class="float-right">{{ \Carbon\Carbon::parse($event->start_time)->format('Y-m-d H:i') }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{{ app()->getLocale() === 'vi' ? 'Thời gian kết thúc' : 'End Time' }}</b>
                        <span class="float-right">
                            {{ $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('Y-m-d H:i') : (app()->getLocale() === 'vi' ? 'Không có' : 'N/A') }}
                        </span>
                    </li>
                    @if($event->location)
                    <li class="list-group-item">
                        <b>{{ app()->getLocale() === 'vi' ? 'Địa điểm' : 'Location' }}</b>
                        <span class="float-right">{{ $event->location }}</span>
                    </li>
                    @endif
                </ul>

                @if($event->is_registered)
                    <div class="alert alert-success">
                        <i class="fas fa-check"></i>
                        {{ app()->getLocale() === 'vi' ? 'Bạn đã tham gia sự kiện này.' : 'You have joined this event.' }}
                        @if($event->preferred_time)
                            <div class="mt-1" style="font-size: 1rem;">
                                {{ app()->getLocale() === 'vi' ? 'Khung giờ ưu tiên:' : 'Preferred Time:' }}
                                {{ $event->preferred_time }}
                            </div>
                        @endif
                        @if($event->type == 'guild_war')
                            <div class="mt-1 d-flex justify-content-between align-items-center" style="font-size: 1rem;">
                                @if(!empty($event->team_name))
                                    <span>
                                        Đội tham gia: {{ $event->team_name }}
                                        <button type="button"
                                                class="btn btn-link p-0 ml-1"
                                                onclick="showTeamMission({{ json_encode($event->team_mission) }})"
                                                title="{{ app()->getLocale() === 'vi' ? 'Xem mô tả nhiệm vụ của đội' : 'View team mission description' }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </span>
                                    @if(!empty($event->team_captain_name))
                                        <span><strong>Đội trưởng: {{ $event->team_captain_name }}</strong></span>
                                    @endif
                                @else
                                    <span>
                                        {{ app()->getLocale() === 'vi' ? 'Trạng thái: chờ sắp xếp đội hình' : 'Status: waiting for formation arrangement' }}
                                        <button type="button"
                                                class="btn btn-link p-0 ml-1"
                                                onclick="showTeamMission(null)"
                                                title="{{ app()->getLocale() === 'vi' ? 'Xem mô tả nhiệm vụ của đội' : 'View team mission description' }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                    @if($event->status == 'upcoming')
                        <form action="{{ route('events.unregister', $event->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('{{ app()->getLocale() === 'vi' ? 'Bạn có chắc chắn muốn rời sự kiện này?' : 'Are you sure you want to leave this event?' }}')">
                                {{ app()->getLocale() === 'vi' ? 'Rời sự kiện' : 'Leave Event' }}
                            </button>
                        </form>
                    @endif
                @else
                    @if($event->type == 'guild_war')
                        <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#joinGuildWarModal{{ $event->id }}">
                            {{ app()->getLocale() === 'vi' ? 'Tham gia Bang chiến' : 'Join Guild War' }}
                        </button>

                        <!-- Modal -->
                        <div class="modal fade" id="joinGuildWarModal{{ $event->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('events.register', $event->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                {{ app()->getLocale() === 'vi' ? 'Tham gia Bang chiến' : 'Join Guild War' }}
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>{{ app()->getLocale() === 'vi' ? 'Khung giờ bạn có thể tham gia (bắt buộc)' : 'Preferred Time (Required)' }}</label>
                                                <input type="text" name="preferred_time" class="form-control" placeholder="{{ app()->getLocale() === 'vi' ? 'VD: 20:00 - 21:00' : 'e.g. 20:00 - 21:00' }}" required>
                                                <small class="form-text text-muted">
                                                    {{ app()->getLocale() === 'vi' ? 'Vui lòng ghi rõ khung giờ bạn có thể tham gia.' : 'Please specify when you can participate.' }}
                                                </small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                                {{ app()->getLocale() === 'vi' ? 'Đóng' : 'Close' }}
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                {{ app()->getLocale() === 'vi' ? 'Tham gia' : 'Join' }}
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
                                {{ app()->getLocale() === 'vi' ? 'Tham gia sự kiện' : 'Join Event' }}
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
                    {{ app()->getLocale() === 'vi' ? 'Mô tả nhiệm vụ của đội' : 'Team mission description' }}
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
                    {{ app()->getLocale() === 'vi' ? 'Đóng' : 'Close' }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function showTeamMission(description) {
        var message = description;
        if (!message) {
            message = @json(app()->getLocale() === 'vi'
                ? 'Chưa có mô tả nhiệm vụ cho đội này.'
                : 'No mission description has been set for this team.'
            );
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
