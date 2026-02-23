@extends('layouts.admin')

@section('title', __('messages.user_dashboard'))

@section('content')
<div class="row">
    <div class="col-md-4">
        <!-- User Profile Card -->
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    @if(Auth::user()->discord_avatar)
                        <img class="profile-user-img img-fluid img-circle"
                             src="{{ Auth::user()->discord_avatar }}"
                             alt="User profile picture">
                    @else
                        <img class="profile-user-img img-fluid img-circle"
                             src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}"
                             alt="User profile picture">
                    @endif
                </div>

                <h3 class="profile-username text-center">{{ Auth::user()->name }}</h3>
                <p class="text-muted text-center">{{ Auth::user()->email }}</p>

                <ul class="list-group list-group-unbordered mb-3" v-pre>
                    <li class="list-group-item">
                        <b>{{ __('messages.discord_id') }}</b> <a class="float-right">{{ Auth::user()->discord_id }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{{ __('messages.ingame_name') }}</b> <a class="float-right">{{ Auth::user()->ingame_name ?? __('messages.not_available') }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{{ __('messages.country') }}</b> <a class="float-right">{{ Auth::user()->country ?? __('messages.not_available') }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{{ __('messages.online_time') }}</b>
                        <a class="float-right">
                            @if(Auth::user()->online_from && Auth::user()->online_to)
                                {{ Auth::user()->online_from }} - {{ Auth::user()->online_to }}
                            @else
                                {{ __('messages.not_available') }}
                            @endif
                        </a>
                    </li>
                    <li class="list-group-item">
                        <b>{{ __('messages.main_skill') }}</b>
                        <div class="float-right">
                            @if(Auth::user()->mainSkill)
                                <img src="{{ asset('icon/skill/' . Auth::user()->mainSkill->icon) }}" width="20" height="20" class="mr-1">
                                {{ Auth::user()->mainSkill->name }}
                            @else
                                <span class="text-muted">{{ __('messages.none') }}</span>
                            @endif
                        </div>
                    </li>
                    <li class="list-group-item">
                        <b>{{ __('messages.sub_skill') }}</b>
                        <div class="float-right">
                            @if(Auth::user()->subSkill)
                                <img src="{{ asset('icon/skill/' . Auth::user()->subSkill->icon) }}" width="20" height="20" class="mr-1">
                                {{ Auth::user()->subSkill->name }}
                            @else
                                <span class="text-muted">{{ __('messages.none') }}</span>
                            @endif
                        </div>
                    </li>
                </ul>

                <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-block"><b>{{ __('messages.edit_profile') }}</b></a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.my_inner_ways') }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach(Auth::user()->innerWays as $innerWay)
                        <div class="col-lg-4 col-md-6 col-sm-6 text-center mb-4">
                            <div class="p-3 border rounded shadow-sm bg-light h-100">
                                <img src="{{ asset('icon/inner_way/' . $innerWay->icon) }}"
                                     alt="{{ $innerWay->name }}"
                                     class="img-fluid mb-3"
                                     style="height: 80px; width: 80px; object-fit: contain;">
                                <h6 class="font-weight-bold">{{ $innerWay->name }}</h6>
                                <span class="badge badge-success px-3 py-2">{{ __('messages.level') }} {{ $innerWay->pivot->level }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
