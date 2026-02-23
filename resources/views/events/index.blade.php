@extends('layouts.admin')

@section('title', 'Available Events')

@section('content')
<div class="row">
    @if($events->isEmpty())
        <div class="col-12">
            <div class="alert alert-info">
                No upcoming or ongoing events at the moment.
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
                        <b>Start Time</b> <span class="float-right">{{ \Carbon\Carbon::parse($event->start_time)->format('Y-m-d H:i') }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>End Time</b> <span class="float-right">{{ $event->end_time ? \Carbon\Carbon::parse($event->end_time)->format('Y-m-d H:i') : 'N/A' }}</span>
                    </li>
                    @if($event->location)
                    <li class="list-group-item">
                        <b>Location</b> <span class="float-right">{{ $event->location }}</span>
                    </li>
                    @endif
                </ul>

                @if($event->is_registered)
                    <div class="alert alert-success">
                        <i class="fas fa-check"></i> You have joined this event.
                        @if($event->preferred_time)
                            <div class="mt-1" style="font-size: 1rem;">
                                Preferred Time: {{ $event->preferred_time }}
                            </div>
                        @endif
                        @if($event->type == 'guild_war')
                            <div class="mt-1 d-flex justify-content-between" style="font-size: 1rem;">
                                @if(!empty($event->team_name))
                                    <span>Đội tham gia: {{ $event->team_name }}</span>
                                    @if(!empty($event->team_captain_name))
                                        <span><strong>Đội trưởng: {{ $event->team_captain_name }}</strong></span>
                                    @endif
                                @else
                                    <span>Trạng thái: chờ sắp xếp đội hình</span>
                                @endif
                            </div>
                        @endif
                    </div>
                    @if($event->status == 'upcoming')
                        <form action="{{ route('events.unregister', $event->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to leave this event?')">Leave Event</button>
                        </form>
                    @endif
                @else
                    @if($event->type == 'guild_war')
                        <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#joinGuildWarModal{{ $event->id }}">
                            Join Guild War
                        </button>

                        <!-- Modal -->
                        <div class="modal fade" id="joinGuildWarModal{{ $event->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('events.register', $event->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Join Guild War</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Preferred Time (Required)</label>
                                                <input type="text" name="preferred_time" class="form-control" placeholder="e.g. 20:00 - 21:00" required>
                                                <small class="form-text text-muted">Please specify when you can participate.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary">Join</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <form action="{{ route('events.register', $event->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-block">Join Event</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
