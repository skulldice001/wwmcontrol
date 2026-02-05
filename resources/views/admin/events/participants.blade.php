@extends('layouts.admin')

@section('title', __('messages.participants_for', ['event' => $event->title]))

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.participants_for', ['event' => $event->title]) }}</h3>
                <div class="card-tools">
                    <a href="{{ route('admin.events.index') }}" class="btn btn-tool">
                        <i class="fas fa-arrow-left"></i> {{ __('messages.back_to_events') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter Form -->
                <form method="GET" action="{{ route('admin.events.participants', $event->id) }}" class="mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('messages.main_skill') }}</label>
                                <select name="main_skill_id" class="form-control select2-icon" style="width: 100%;">
                                    <option value="">{{ __('messages.filter') }} {{ __('messages.main_skill') }}</option>
                                    @foreach($skills as $skill)
                                        <option value="{{ $skill->id }}"
                                            data-icon="{{ asset('icon/skill/' . $skill->icon) }}"
                                            {{ request('main_skill_id') == $skill->id ? 'selected' : '' }}>
                                            {{ $skill->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('messages.sub_skill') }}</label>
                                <select name="sub_skill_id" class="form-control select2-icon" style="width: 100%;">
                                    <option value="">{{ __('messages.filter') }} {{ __('messages.sub_skill') }}</option>
                                    @foreach($skills as $skill)
                                        <option value="{{ $skill->id }}"
                                            data-icon="{{ asset('icon/skill/' . $skill->icon) }}"
                                            {{ request('sub_skill_id') == $skill->id ? 'selected' : '' }}>
                                            {{ $skill->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> {{ __('messages.search') }}
                                    </button>
                                    <a href="{{ route('admin.events.participants', $event->id) }}" class="btn btn-default">
                                        <i class="fas fa-undo"></i> {{ __('messages.reset') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                @if($participants->isEmpty())
                    <div class="alert alert-info">
                        {{ __('messages.no_participants') }}
                    </div>
                @else
                    <table id="participantsTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>{{ __('messages.discord_name') }}</th>
                                <th>Ingame Name</th>
                                <th>{{ __('messages.country') }}</th>
                                <th>{{ __('messages.main_skill') }}</th>
                                <th>{{ __('messages.sub_skill') }}</th>
                                <th>Inner Ways</th>
                                @if($event->type == 'guild_war')
                                    <th>{{ __('messages.preferred_time') }}</th>
                                @endif
                                <th>{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($participants as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>
                                        <div class="user-block">
                                            @if($user->discord_avatar)
                                                <img class="img-circle img-bordered-sm" src="{{ $user->discord_avatar }}" alt="User Image">
                                            @else
                                                <img class="img-circle img-bordered-sm" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" alt="User Image">
                                            @endif
                                            <span class="username">
                                                <a href="#">{{ $user->name }}</a>
                                            </span>
                                            <span class="description">
                                                {{ $user->email }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>{{ $user->ingame_name ?? 'N/A' }}</td>
                                    <td>{{ $user->country ?? 'N/A' }}</td>
                                    <td>
                                        @if($user->mainSkill)
                                            <div class="d-flex align-items-center" title="{{ $user->mainSkill->name }}">
                                                <img src="{{ asset('icon/skill/' . $user->mainSkill->icon) }}" width="24" height="24" class="mr-2 rounded">
                                            </div>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->subSkill)
                                            <div class="d-flex align-items-center" title="{{ $user->subSkill->name }}">
                                                <img src="{{ asset('icon/skill/' . $user->subSkill->icon) }}" width="24" height="24" class="mr-2 rounded">
                                            </div>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap" v-pre>
                                            @foreach($user->innerWays as $iw)
                                                <div class="d-inline-block text-center mr-1 mb-1 position-relative" title="{{ $iw->name }}">
                                                    <img src="{{ asset('icon/inner_way/' . $iw->icon) }}" width="32" height="32" class="img-fluid" alt="{{ $iw->name }}">
                                                    <span class="badge badge-light border" style="position: absolute; bottom: -5px; right: -5px; font-size: 10px; padding: 2px 4px;">{{ $iw->pivot->level }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                    @if($event->type == 'guild_war')
                                        <td>{{ $user->pivot->preferred_time ?? 'N/A' }}</td>
                                    @endif
                                    <td>
                                        <form action="{{ route('admin.events.remove_participant', ['event' => $event->id, 'user' => $user->id]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to remove this participant?')">
                                                <i class="fas fa-trash"></i> {{ __('messages.delete') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    $(document).ready(function() {
        // Format for the selected item (in the closed box)
        function formatState (state) {
            if (!state.id) {
                return state.text;
            }
            var iconUrl = $(state.element).data('icon');
            var $state = $('<span></span>');
            if(iconUrl){
                $state.append('<img src="' + iconUrl + '" class="img-flag" width="24" height="24" style="margin-right: 10px;" />');
            }
            $state.append(state.text);
            return $state;
        }

        // Format for the dropdown list options
        function formatOption (state) {
            if (!state.id) {
                return state.text;
            }
            var iconUrl = $(state.element).data('icon');
            var $container = $('<div class="d-flex align-items-center justify-content-between" style="width: 100%;"></div>');
            var $left = $('<div class="d-flex align-items-center"></div>');

            if(iconUrl){
                $left.append('<img src="' + iconUrl + '" class="img-flag" width="24" height="24" style="margin-right: 10px;" />');
            }
            $left.append('<span>' + state.text + '</span>');
            $container.append($left);

            // Add checkmark if selected
            if (state.selected) {
                 $container.append('<i class="fas fa-check"></i>');
            }

            return $container;
        }

        $('.select2-icon').select2({
            templateResult: formatOption,
            templateSelection: formatState,
            width: '100%',
            escapeMarkup: function(m) { return m; }
        });
    });
</script>
@endpush
