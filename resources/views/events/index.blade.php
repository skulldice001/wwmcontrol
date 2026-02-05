@extends('layouts.admin')

@section('title', __('messages.available_events'))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.available_events') }}</h3>
            </div>
            <div class="card-body">
                <table id="eventsTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('messages.title') }}</th>
                            <th>{{ __('messages.type') }}</th>
                            <th>{{ __('messages.start_time') }}</th>
                            <th>{{ __('messages.location') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th>{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $event)
                            <tr>
                                <td>{{ $event->id }}</td>
                                <td>{{ $event->title }}</td>
                                <td>{{ ucfirst($event->type) }}</td>
                                <td>{{ $event->start_time }}</td>
                                <td>{{ $event->location }}</td>
                                <td>
                                    @if($event->status == 'scheduled')
                                        <span class="badge badge-primary">Scheduled</span>
                                    @elseif($event->status == 'ongoing')
                                        <span class="badge badge-success">Ongoing</span>
                                    @elseif($event->status == 'completed')
                                        <span class="badge badge-secondary">Completed</span>
                                    @else
                                        <span class="badge badge-danger">Cancelled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($event->participants->contains(Auth::id()))
                                        <span class="badge badge-success">{{ __('messages.joined_event') }}</span>
                                        <form action="{{ route('events.leave', $event->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger">{{ __('messages.leave_event') }}</button>
                                        </form>
                                    @else
                                        @if($event->type === 'guild_war')
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#joinGuildWarModal{{ $event->id }}">
                                                {{ __('messages.join_guild_war') }}
                                            </button>
                                        @else
                                            <form action="{{ route('events.join', $event->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">{{ __('messages.join_event') }}</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>

                            <!-- Join Guild War Modal -->
                            @if($event->type === 'guild_war' && !$event->participants->contains(Auth::id()))
                            <div class="modal fade" id="joinGuildWarModal{{ $event->id }}" tabindex="-1" role="dialog" aria-labelledby="joinGuildWarModalLabel{{ $event->id }}" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <form action="{{ route('events.join', $event->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="joinGuildWarModalLabel{{ $event->id }}">{{ __('messages.join_guild_war') }}</h5>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="preferred_time">{{ __('messages.preferred_time') }}</label>
                                                    <select name="preferred_time" id="preferred_time" class="form-control" required>
                                                        <option value="20:00">20:00</option>
                                                        <option value="21:00">21:00</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('messages.close') }}</button>
                                                <button type="submit" class="btn btn-primary">{{ __('messages.join') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
