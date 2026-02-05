@extends('layouts.admin')

@section('title', __('messages.dashboard'))

@section('content')
<div class="container-fluid">
    <h1 class="m-0">{{ __('messages.welcome_back') }}, {{ Auth::user()->name }}!</h1>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.my_inner_ways') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row" v-pre>
                        @foreach(Auth::user()->innerWays as $iw)
                            <div class="d-inline-block text-center mr-2 position-relative" title="{{ $iw->name }}">
                                <img src="{{ asset('icon/inner_way/' . $iw->icon) }}" width="32" height="32" class="img-fluid" alt="{{ $iw->name }}">
                                <span class="badge badge-light border" style="position: absolute; bottom: -5px; right: -5px; font-size: 10px; padding: 2px 4px;">{{ $iw->pivot->level }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('messages.my_skills') }}</h3>
                </div>
                <div class="card-body">
                    <div class="row" v-pre>
                        <div class="col-md-6">
                            <strong>{{ __('messages.main_skill') }}:</strong>
                            @if(Auth::user()->mainSkill)
                                <div class="d-inline-block text-center ml-2 position-relative" title="{{ Auth::user()->mainSkill->name }}">
                                    <img src="{{ asset('icon/skill/' . Auth::user()->mainSkill->icon) }}" width="32" height="32" class="img-fluid" alt="{{ Auth::user()->mainSkill->name }}">
                                    <span>{{ Auth::user()->mainSkill->name }}</span>
                                </div>
                            @else
                                <span class="text-muted">None</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <strong>{{ __('messages.sub_skill') }}:</strong>
                            @if(Auth::user()->subSkill)
                                <div class="d-inline-block text-center ml-2 position-relative" title="{{ Auth::user()->subSkill->name }}">
                                    <img src="{{ asset('icon/skill/' . Auth::user()->subSkill->icon) }}" width="32" height="32" class="img-fluid" alt="{{ Auth::user()->subSkill->name }}">
                                    <span>{{ Auth::user()->subSkill->name }}</span>
                                </div>
                            @else
                                <span class="text-muted">None</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
