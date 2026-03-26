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
                    <th>{{ __('messages.account') }}</th>
                    <th>{{ __('messages.ingame_name') }}</th>
                    <th>{{ __('messages.main_skill') }}</th>
                    <th>{{ __('messages.role') }}</th>
                    <th>{{ __('messages.sub_skill') }}</th>
                    <th>{{ __('messages.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="{{ $user->trashed() ? 'table-danger' : '' }}">
                    <td>
                        @if($user->discord_avatar)
                            <img src="{{ $user->discord_avatar }}" class="img-circle elevation-2" alt="User Image" style="width: 30px; height: 30px;">
                        @else
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" class="img-circle elevation-2" alt="User Image" style="width: 30px; height: 30px;">
                        @endif
                    </td>
                    <td>
                        {{ $user->name }}
                        @if($user->trashed())
                            <span class="badge badge-danger ml-1">Disabled</span>
                        @endif
                    </td>
                    <td>{{ $user->account }}</td>
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
                        @if($user->trashed())
                            <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-trash-restore"></i> {{ __('messages.restore') }}
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('{{ __('messages.confirm_disable') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-ban"></i> {{ __('messages.disable') }}
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
            {{-- Z-Coin section --}}
            <div class="modal-body border-top pt-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="fas fa-coins mr-1" style="color:#f6c23e;"></i> Zoo</h6>
                    <a href="{{ route('admin.users.coin-history', $user->id) }}" class="btn btn-xs btn-outline-warning" target="_blank">
                        <i class="fas fa-history mr-1"></i>{{ __('messages.zcoin_history_title') }}
                    </a>
                </div>
                <div class="row mb-3">
                    <div class="col-4 text-center">
                        <div class="text-muted small">{{ __('messages.zcoin_total') }}</div>
                        <strong style="color:#f6c23e;">{{ number_format($user->z_coins) }} Zoo</strong>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">{{ __('messages.zcoin_frozen') }}</div>
                        <strong style="color:#e74c3c;">{{ number_format($user->z_coins_frozen) }} Zoo</strong>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">{{ __('messages.zcoin_available') }}</div>
                        <strong style="color:#2ecc71;">{{ number_format($user->z_coins - $user->z_coins_frozen) }} Zoo</strong>
                    </div>
                </div>

                {{-- Freeze coins (admin+master) --}}
                <form action="{{ route('admin.users.freeze-coins', $user->id) }}" method="POST" class="form-inline justify-content-center mb-3">
                    @csrf
                    <label class="mr-2">{{ __('messages.zcoin_set_freeze') }}:</label>
                    <input type="number" name="amount" class="form-control form-control-sm mr-2"
                           style="width:130px"
                           min="0" max="{{ $user->z_coins }}"
                           value="{{ $user->z_coins_frozen }}" required>
                    <button type="submit" class="btn btn-sm btn-warning">
                        <i class="fas fa-lock mr-1"></i>{{ __('messages.zcoin_freeze') }}
                    </button>
                </form>

                {{-- Adjust coins (master only) --}}
                @if(auth()->guard('staff')->user()->isMaster())
                <div class="border-top pt-3 mt-1">
                    <p class="small text-muted mb-2"><i class="fas fa-crown mr-1 text-warning"></i>{{ __('messages.zcoin_adjust_title') }} (Master)</p>
                    <form action="{{ route('admin.users.adjust-coins', $user->id) }}" method="POST" class="form-inline flex-wrap justify-content-center" style="gap:6px;">
                        @csrf
                        <select name="adjust_type" class="form-control form-control-sm" style="width:90px;" required>
                            <option value="add">+ Nạp</option>
                            <option value="deduct">− Trừ</option>
                        </select>
                        <input type="number" name="adjust_amount" class="form-control form-control-sm"
                               style="width:120px;" min="1" placeholder="Số lượng" required>
                        <input type="text" name="adjust_note" class="form-control form-control-sm"
                               style="width:160px;" placeholder="{{ __('messages.zcoin_tx_note') }}">
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="fas fa-check mr-1"></i>{{ __('messages.zcoin_adjust_btn') }}
                        </button>
                    </form>
                </div>
                @endif
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
            var roleColumnIndex = 5; // Default to 5

            // Find column index by header text to be robust
            table.columns().header().each(function(th, index) {
                if ($(th).data('name') === 'role' || $(th).text().trim() === '{{ __('messages.role') }}') {
                    roleColumnIndex = index;
                }
            });

            table.column(roleColumnIndex).search(value).draw();
        });
    });
</script>
@endpush
