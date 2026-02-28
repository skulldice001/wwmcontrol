@extends('layouts.admin')

@section('title', __('messages.guild_members'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.guild_members_list') }}</h3>
        <div class="card-tools">
            <a href="{{ route('admin.users.create') }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> {{ __('messages.create_user') }}
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="role-filter" class="mr-2">{{ __('messages.filter_by_role') }}</label>
            <select id="role-filter" class="form-control form-control-sm" style="width: 200px; display: inline-block;">
                <option value="">{{ __('messages.all_roles') }}</option>
                @foreach(\App\Constants\SkillRole::getRoles() as $role)
                    <option value="{{ $role }}">{{ $role }}</option>
                @endforeach
            </select>
        </div>
        <table class="table table-bordered table-striped" id="users-table">
            <thead>
                <tr>
                    <th>Avatar</th>
                    <th>{{ __('messages.discord_name') }}</th>
                    <th>{{ __('messages.ingame_name') }}</th>
                    <th>{{ __('messages.main_skill') }}</th>
                    <th>{{ __('messages.role') }}</th>
                    <th>{{ __('messages.sub_skill') }}</th>
                    <th>{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>
                        @if($user->discord_avatar)
                            <img src="{{ $user->discord_avatar }}" class="img-circle elevation-2" alt="User Image" style="width: 30px; height: 30px;">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" class="img-circle elevation-2" alt="User Image" style="width: 30px; height: 30px;">
                        @endif
                    </td>
                    <td>
                        {{ $user->name }}
                    </td>
                    <td>{{ $user->ingame_name ?? __('messages.not_available') }}</td>
                    <td>
                        @if($user->mainSkill)
                            <span class="badge badge-primary">{{ $user->mainSkill->name }}</span>
                        @else
                            <span class="text-muted">{{ __('messages.none') }}</span>
                        @endif
                    </td>
                    <td>
                        {{ \App\Constants\SkillRole::getRole(optional($user->mainSkill)->slug) }}
                    </td>
                    <td>
                        @if($user->subSkill)
                            <span class="badge badge-secondary">{{ $user->subSkill->name }}</span>
                        @else
                            <span class="text-muted">{{ __('messages.none') }}</span>
                        @endif
                    </td>
                    <td>
                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#modal-user-{{ $user->id }}">
                            <i class="fas fa-eye"></i> {{ __('messages.view_stats') }}
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
                <h4 class="modal-title">{{ $user->name }} - {{ __('messages.view_stats') }}</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>{{ __('messages.profile') }}</h5>
                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>{{ __('messages.discord_id') }}</b> <a class="float-right">{{ $user->discord_id }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>{{ __('messages.ingame_id') }}</b> <a class="float-right">{{ $user->ingame_id ?? __('messages.not_available') }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>{{ __('messages.country') }}</b> <a class="float-right">{{ $user->country ?? __('messages.not_available') }}</a>
                            </li>
                            <li class="list-group-item">
                                <b>{{ __('messages.online_time_range') }}</b> <a class="float-right">{{ $user->online_from }} - {{ $user->online_to }}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5>{{ __('messages.inner_ways') }}</h5>
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
                                    <p class="text-muted text-center">{{ __('messages.no_inner_ways') }}</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close_modal') }}</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script type="module">
    $(function () {
        var table = $('#users-table').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });

        $('#role-filter').on('change', function () {
            var value = $(this).val();
            // Role column index (0-based): ID(0), Avatar(1), Discord Name(2), Ingame Name(3), Main Skill(4), Role(5)
            table.column(5).search(value).draw();
        });
    });
</script>
@endpush
