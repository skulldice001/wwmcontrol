@extends('layouts.admin')

@section('title', 'Edit Profile')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">User Information</h3>
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
                        <label>Discord Name</label>
                        <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" class="form-control" id="country" name="country" value="{{ old('country', $user->country) }}" placeholder="Enter country">
                    </div>
                    <div class="row">
                        <div class="col-6">
                             <div class="form-group">
                                <label for="online_from">Online From (24h)</label>
                                <input type="time" class="form-control" id="online_from" name="online_from" value="{{ old('online_from', $user->online_from) }}" step="60">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="online_to">Online To (24h)</label>
                                <input type="time" class="form-control" id="online_to" name="online_to" value="{{ old('online_to', $user->online_to) }}" step="60">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ingame_name">Ingame Name</label>
                        <input type="text" class="form-control" id="ingame_name" name="ingame_name" value="{{ old('ingame_name', $user->ingame_name) }}" placeholder="Enter Ingame Name">
                    </div>
                    <div class="form-group">
                        <label for="ingame_id">Ingame ID</label>
                        <input type="text" class="form-control" id="ingame_id" name="ingame_id" value="{{ old('ingame_id', $user->ingame_id) }}" placeholder="Enter Ingame ID">
                    </div>
                    <div class="row" v-pre>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Main Skill</label>
                                <div class="form-control" disabled style="height: auto; display: flex; align-items: center;">
                                    @if($user->mainSkill)
                                        <img src="{{ asset('icon/skill/' . $user->mainSkill->icon) }}" width="30" height="30" class="mr-2">
                                        <span>{{ $user->mainSkill->name }}</span>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                             <div class="form-group">
                                <label>Sub Skill</label>
                                <div class="form-control" disabled style="height: auto; display: flex; align-items: center;">
                                    @if($user->subSkill)
                                        <img src="{{ asset('icon/skill/' . $user->subSkill->icon) }}" width="30" height="30" class="mr-2">
                                        <span>{{ $user->subSkill->name }}</span>
                                    @else
                                        <span class="text-muted">None</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-info">
             <div class="card-header">
                <h3 class="card-title">Discord Info</h3>
            </div>
            <div class="card-body text-center">
                @if($user->discord_avatar)
                    <img src="{{ $user->discord_avatar }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px;">
                @else
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}" class="img-circle elevation-2 mb-3" alt="User Image" style="width: 100px; height: 100px;">
                @endif
                <h4>{{ $user->name }}</h4>
                <p class="text-muted">ID: {{ $user->discord_id }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
