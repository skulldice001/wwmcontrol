@extends('layouts.admin')

@section('title', __('messages.skills_inner_ways_management'))

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
<div class="row">
    <div class="col-12">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('skills.update') }}" method="POST" v-pre>
            @csrf
            @method('PUT')

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.active_skills') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.main_skill') }}</label>
                                <select name="main_skill_id" class="form-control select2-icon" style="width: 100%;">
                                    <option value="">{{ __('messages.select_main_skill') }}</option>
                                    @foreach($skills as $skill)
                                        <option value="{{ $skill->id }}" data-icon="{{ asset('icon/skill/' . $skill->icon) }}" {{ $user->main_skill_id == $skill->id ? 'selected' : '' }}>
                                            {{ $skill->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.sub_skill') }}</label>
                                <select name="sub_skill_id" class="form-control select2-icon" style="width: 100%;">
                                    <option value="">{{ __('messages.select_sub_skill') }}</option>
                                    @foreach($skills as $skill)
                                        <option value="{{ $skill->id }}" data-icon="{{ asset('icon/skill/' . $skill->icon) }}" {{ $user->sub_skill_id == $skill->id ? 'selected' : '' }}>
                                            {{ $skill->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.inner_ways_configuration') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($innerWays as $iw)
                            @php
                                $userIw = $user->innerWays->firstWhere('slug', $iw->slug);
                                $currentLevel = $userIw ? $userIw->pivot->level : 0;
                            @endphp
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="border rounded p-3 text-center {{ $currentLevel > 0 ? 'bg-light' : '' }}">
                                    <img src="{{ asset('icon/inner_way/' . $iw->icon) }}" alt="{{ $iw->name }}" style="width: 100px; height: 100px; object-fit: contain;" class="mb-2">
                                    <h6 class="font-weight-bold text-truncate" title="{{ $iw->name }}">{{ $iw->name }}</h6>

                                    <div class="form-group mb-0 mt-2">
                                        <label class="small">Level (0-6)</label>
                                        <input type="number"
                                               name="inner_ways[{{ $iw->slug }}]"
                                               class="form-control form-control-sm text-center"
                                               min="0" max="6"
                                               oninput="if(this.value > 6) this.value = 6; if(this.value < 0) this.value = 0;"
                                               value="{{ $currentLevel }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block">{{ __('messages.save_changes') }}</button>
                </div>
            </div>
        </form>
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
