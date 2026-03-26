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

        {{-- Zoo-coin card --}}
        <div class="card card-warning card-outline mb-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-coins mr-2" style="color:#f6c23e;"></i>
                    Zoo-coin
                    <strong class="ml-2" style="color:#f6c23e;">{{ number_format(Auth::user()->z_coins) }} Zoo</strong>
                    @if(Auth::user()->z_coins_frozen > 0)
                        <small class="text-danger ml-2">
                            <i class="fas fa-lock"></i> {{ number_format(Auth::user()->z_coins_frozen) }} Zoo {{ __('messages.zcoin_frozen') }}
                        </small>
                    @endif
                </h3>
                <div class="card-tools">
                    <a href="{{ route('zoo.history') }}" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-history mr-1"></i>{{ __('messages.zcoin_history_title') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(session('zoo_success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('zoo_success') }}
                    </div>
                @endif
                @if(session('zoo_error'))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        {{ session('zoo_error') }}
                    </div>
                @endif

                <form action="{{ route('zoo.transfer') }}" method="POST">
                    @csrf
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-4 mb-2">
                            <label class="small mb-1">{{ __('messages.zcoin_transfer_recipient') }}</label>
                            <input type="text" name="recipient_account" class="form-control form-control-sm"
                                   placeholder="{{ __('messages.zcoin_transfer_account_placeholder') }}"
                                   value="{{ old('recipient_account') }}" required>
                        </div>
                        <div class="form-group col-md-3 mb-2">
                            <label class="small mb-1">{{ __('messages.zcoin_transfer_amount') }}</label>
                            <input type="number" name="amount" class="form-control form-control-sm"
                                   placeholder="Zoo" min="1" value="{{ old('amount') }}" required>
                        </div>
                        <div class="form-group col-md-3 mb-2">
                            <label class="small mb-1">{{ __('messages.zcoin_tx_note') }}</label>
                            <input type="text" name="note" class="form-control form-control-sm"
                                   placeholder="{{ __('messages.zcoin_transfer_note_placeholder') }}"
                                   value="{{ old('note') }}">
                        </div>
                        <div class="form-group col-md-2 mb-2">
                            <button type="submit" class="btn btn-warning btn-sm btn-block">
                                <i class="fas fa-paper-plane mr-1"></i>{{ __('messages.zcoin_transfer_btn') }}
                            </button>
                        </div>
                    </div>
                    <small class="text-muted">
                        {{ __('messages.zcoin_available') }}: <strong style="color:#f6c23e;">{{ number_format(Auth::user()->availableZCoins()) }} Zoo</strong>
                    </small>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.my_inner_ways') }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach(Auth::user()->innerWays as $innerWay)
                        @if($innerWay->pivot->level > 0)
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
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
