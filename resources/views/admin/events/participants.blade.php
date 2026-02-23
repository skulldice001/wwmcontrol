@extends('layouts.admin')

@section('title', __('messages.event_participants_title'))

@section('content')
@push('styles')
<style>
    .select2-container .select2-selection--single {
        height: 50px !important;
        display: flex !important;
        align-items: center !important;
        font-size: 16px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 50px !important;
        padding-left: 10px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 50px !important;
        right: 10px !important;
    }
    .select2-results__option {
        padding: 10px !important;
        font-size: 16px !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-results__option img {
        width: 32px !important;
        height: 32px !important;
    }
</style>
@endpush
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.participants_for') }} {{ $event->title }}</h3>
        <div class="card-tools">
            <a href="{{ route('admin.events.index') }}" class="btn btn-default btn-sm">{{ __('messages.back_to_events_admin') }}</a>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <strong>{{ __('messages.participants_label') }}</strong>
            {{ $participants->count() }} / {{ $totalParticipants }}
        </div>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.events.participants', $event->id) }}" class="mb-4" v-pre>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ __('messages.main_skill_filter') }}</label>
                        <select name="main_skill_id" class="form-control select2-icon" style="width: 100%;">
                            <option value="">{{ __('messages.all_main_skills') }}</option>
                            @foreach($skills as $skill)
                                <option value="{{ $skill->id }}" data-icon="{{ asset('icon/skill/' . $skill->icon) }}" {{ request('main_skill_id') == $skill->id ? 'selected' : '' }}>
                                    {{ $skill->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ __('messages.sub_skill_filter') }}</label>
                        <select name="sub_skill_id" class="form-control select2-icon" style="width: 100%;">
                            <option value="">{{ __('messages.all_sub_skills') }}</option>
                            @foreach($skills as $skill)
                                <option value="{{ $skill->id }}" data-icon="{{ asset('icon/skill/' . $skill->icon) }}" {{ request('sub_skill_id') == $skill->id ? 'selected' : '' }}>
                                    {{ $skill->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ __('messages.inner_way_filter') }}</label>
                        <select name="inner_way_id" class="form-control select2-icon" style="width: 100%;">
                            <option value="">{{ __('messages.all_inner_ways') }}</option>
                            @foreach($innerWays as $iw)
                                <option value="{{ $iw->id }}" data-icon="{{ asset('icon/inner_way/' . $iw->icon) }}" {{ request('inner_way_id') == $iw->id ? 'selected' : '' }}>
                                    {{ $iw->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ __('messages.min_level') }}</label>
                        <input type="number" name="inner_way_level" class="form-control" placeholder="{{ __('messages.min_level') }}" value="{{ request('inner_way_level') }}" min="1" max="10">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">{{ __('messages.filter') }}</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>{{ __('messages.role_filter') }}</label>
                        <select name="role" class="form-control">
                            <option value="">{{ __('messages.all_roles_filter') }}</option>
                            @foreach(\App\Constants\SkillRole::getRoles() as $role)
                                <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                                    {{ $role }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <!-- Participants List -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('messages.event_id') }}</th>
                        <th>{{ __('messages.name') }}</th>
                        <th>{{ __('messages.in_game_name') }}</th>
                        <th>{{ __('messages.main_skill') }}</th>
                        <th>{{ __('messages.role') }}</th>
                        <th>{{ __('messages.sub_skill') }}</th>
                        <th>{{ __('messages.inner_ways_column') }}</th>
                        <th>{{ __('messages.preferred_time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->ingame_name ?? __('messages.not_available') }}</td>
                        <td>
                            @if($user->mainSkill)
                                <img src="{{ asset('icon/skill/' . $user->mainSkill->icon) }}" width="24" height="24"> {{ $user->mainSkill->name }}
                            @else
                                {{ __('messages.not_available') }}
                            @endif
                        </td>
                        <td>{{ \App\Constants\SkillRole::getRole(optional($user->mainSkill)->slug) }}</td>
                        <td>
                            @if($user->subSkill)
                                <img src="{{ asset('icon/skill/' . $user->subSkill->icon) }}" width="24" height="24"> {{ $user->subSkill->name }}
                            @else
                                {{ __('messages.not_available') }}
                            @endif
                        </td>
                        <td>
                            @php
                                $innerWaysData = $user->innerWays->map(function ($iw) {
                                    return [
                                        'name' => $iw->name,
                                        'icon' => asset('icon/inner_way/' . $iw->icon),
                                        'level' => $iw->pivot->level,
                                    ];
                                });
                            @endphp
                            <inner-ways-cell
                                :inner-ways='@json($innerWaysData)'
                            ></inner-ways-cell>
                        </td>
                        <td>{{ $user->pivot->preferred_time ?? __('messages.not_available') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">{{ __('messages.no_participants_found') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
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
