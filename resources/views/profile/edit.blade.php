@extends('layouts.admin')

@section('title', __('messages.edit_profile_title'))

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.user_information') }}</h3>
            </div>

            @if (session('success'))
                <div class="alert alert-success m-3">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label>{{ __('messages.discord_name') }}</label>
                        <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.email') }}</label>
                        <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="country">{{ __('messages.country') }}</label>
                        <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $user->country) }}" placeholder="{{ __('messages.enter_country') }}">
                    </div>
                    <div class="row">
                        <div class="col-6">
                             <div class="form-group">
                                <label for="online_from">{{ __('messages.online_from_24h') }}</label>
                                <input type="time" class="form-control" id="online_from" name="online_from" value="{{ old('online_from', $user->online_from) }}" step="60">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="online_to">{{ __('messages.online_to_24h') }}</label>
                                <input type="time" class="form-control" id="online_to" name="online_to" value="{{ old('online_to', $user->online_to) }}" step="60">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ingame_name">{{ __('messages.ingame_name') }}</label>
                        <input type="text" class="form-control" id="ingame_name" name="ingame_name" value="{{ old('ingame_name', $user->ingame_name) }}" placeholder="{{ __('messages.enter_ingame_name') }}">
                    </div>
                    <div class="form-group">
                        <label for="ingame_id">{{ __('messages.ingame_id') }}</label>
                        <input type="text" class="form-control" id="ingame_id" name="ingame_id" value="{{ old('ingame_id', $user->ingame_id) }}" placeholder="{{ __('messages.enter_ingame_id') }}">
                    </div>
                    <div class="row" v-pre>
                        <div class="col-6">
                            <div class="form-group">
                                <label>{{ __('messages.main_skill_label') }}</label>
                                <div class="form-control" disabled style="height: auto; display: flex; align-items: center;">
                                    @if($user->mainSkill)
                                        <img src="{{ asset('icon/skill/' . $user->mainSkill->icon) }}" width="30" height="30" class="mr-2">
                                        <span>{{ $user->mainSkill->name }}</span>
                                    @else
                                        <span class="text-muted">{{ __('messages.none_text') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                             <div class="form-group">
                                <label>{{ __('messages.sub_skill_label') }}</label>
                                <div class="form-control" disabled style="height: auto; display: flex; align-items: center;">
                                    @if($user->subSkill)
                                        <img src="{{ asset('icon/skill/' . $user->subSkill->icon) }}" width="30" height="30" class="mr-2">
                                        <span>{{ $user->subSkill->name }}</span>
                                    @else
                                        <span class="text-muted">{{ __('messages.none_text') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">{{ __('messages.update_profile') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-info">
             <div class="card-header">
                <h3 class="card-title">{{ __('messages.discord_info') }}</h3>
            </div>
            <div class="card-body text-center">
                @if($user->discord_avatar)
                    <img src="{{ $user->discord_avatar }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px;">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px;">
                @endif
                <h4>{{ $user->name }}</h4>
                <p class="text-muted">{{ __('messages.id_label') }} {{ $user->discord_id }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
