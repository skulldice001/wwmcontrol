@extends('layouts.admin')

@section('title', 'Guild Members')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Guild Members List</h3>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-striped" id="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Avatar</th>
                    <th>Discord Name</th>
                    <th>Ingame Name</th>
                    <th>Main Skill</th>
                    <th>Sub Skill</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>
                        @if($user->discord_avatar)
                            <img src="{{ $user->discord_avatar }}" class="img-circle elevation-2" alt="User Image" style="width: 30px; height: 30px;">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" class="img-circle elevation-2" alt="User Image" style="width: 30px; height: 30px;">
                        @endif
                    </td>
                    <td>
                        {{ $user->name }}
                        <br>
                        <small class="text-muted">{{ $user->email }}</small>
                    </td>
                    <td>{{ $user->ingame_name ?? 'N/A' }}</td>
                    <td>
                        @if($user->mainSkill)
                            <span class="badge badge-primary">{{ $user->mainSkill->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($user->subSkill)
                            <span class="badge badge-secondary">{{ $user->subSkill->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modal-user-{{ $user->id }}">
                            <i class="fas fa-eye"></i> View Stats
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@foreach($users as $user)
<div class="modal fade" id="modal-user-{{ $user->id }}">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ $user->name }}'s Stats</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Profile</h5>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Discord ID</b> <a class="float-right">{{ $user->discord_id }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Ingame ID</b> <a class="float-right">{{ $user->ingame_id ?? 'N/A' }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Country</b> <a class="float-right">{{ $user->country ?? 'N/A' }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>Online Time</b> <a class="float-right">{{ $user->online_from }} - {{ $user->online_to }}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>Inner Ways</h5>
                        <div class="row">
                            @forelse($user->innerWays as $iw)
                                @if($iw->pivot->level > 0)
                                <div class="col-6 mb-2">
                                    <div class="border rounded p-2 text-center bg-light">
                                        <small class="font-weight-bold d-block text-truncate">{{ $iw->name }}</small>
                                        <span class="badge badge-success">Level {{ $iw->pivot->level }}</span>
                                    </div>
                                </div>
                                @endif
                            @empty
                                <div class="col-12">
                                    <p class="text-muted text-center">No inner ways configured.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script>
    $(function () {
        $('#users-table').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });
    });
</script>
@endpush
