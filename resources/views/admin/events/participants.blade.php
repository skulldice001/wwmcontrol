@extends('layouts.admin')

@section('title', 'Event Participants')

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
        <h3 class="card-title">Participants for: {{ $event->title }}</h3>
        <div class="card-tools">
            <a href="{{ route('admin.events.index') }}" class="btn btn-default btn-sm">Back to Events</a>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <strong>Participants:</strong>
            {{ $participants->count() }} / {{ $totalParticipants }}
        </div>
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.events.participants', $event->id) }}" class="mb-4" v-pre>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Main Skill</label>
                        <select name="main_skill_id" class="form-control select2-icon" style="width: 100%;">
                            <option value="">All Main Skills</option>
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
                        <label>Sub Skill</label>
                        <select name="sub_skill_id" class="form-control select2-icon" style="width: 100%;">
                            <option value="">All Sub Skills</option>
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
                        <label>Inner Way</label>
                        <select name="inner_way_id" class="form-control select2-icon" style="width: 100%;">
                            <option value="">All Inner Ways</option>
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
                        <label>Min Level</label>
                        <input type="number" name="inner_way_level" class="form-control" placeholder="Min Level" value="{{ request('inner_way_level') }}" min="1" max="10">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="">All Roles</option>
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
                        <th>ID</th>
                        <th>Name</th>
                        <th>In-game Name</th>
                        <th>Main Skill</th>
                        <th>Role</th>
                        <th>Sub Skill</th>
                        <th>Inner Ways</th>
                        <th>Preferred Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->ingame_name ?? 'N/A' }}</td>
                        <td>
                            @if($user->mainSkill)
                                <img src="{{ asset('icon/skill/' . $user->mainSkill->icon) }}" width="24" height="24"> {{ $user->mainSkill->name }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ \App\Constants\SkillRole::getRole(optional($user->mainSkill)->slug) }}</td>
                        <td>
                            @if($user->subSkill)
                                <img src="{{ asset('icon/skill/' . $user->subSkill->icon) }}" width="24" height="24"> {{ $user->subSkill->name }}
                            @else
                                N/A
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
                        <td>{{ $user->pivot->preferred_time ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">No participants found matching your criteria.</td>
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
