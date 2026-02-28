@extends('layouts.admin')

@section('title', __('messages.create_user'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.create_new_user') }}</h3>
    </div>
    <!-- /.card-header -->
    <!-- form start -->
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label for="name">{{ __('messages.name') }}</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="{{ __('messages.enter_name') }}">
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="account">{{ __('messages.account') }}</label>
                <input type="text" class="form-control @error('account') is-invalid @enderror" id="account" name="account" value="{{ old('account') }}" placeholder="{{ __('messages.enter_account') }}" required>
                @error('account')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="email">{{ __('messages.email') }}</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="{{ __('messages.enter_email') }}">
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="password">{{ __('messages.password') }}</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="{{ __('messages.enter_password') }}" required>
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation">{{ __('messages.confirm_password') }}</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="{{ __('messages.confirm_password') }}" required>
            </div>
            <div class="form-group">
                <label for="ingame_name">{{ __('messages.ingame_name') }}</label>
                <input type="text" class="form-control @error('ingame_name') is-invalid @enderror" id="ingame_name" name="ingame_name" value="{{ old('ingame_name') }}" placeholder="{{ __('messages.enter_ingame_name') }}">
                @error('ingame_name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="country">{{ __('messages.country') }}</label>
                <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country') }}" placeholder="{{ __('messages.enter_country') }}">
                @error('country')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <!-- /.card-body -->

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('messages.create') }}</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-default float-right">{{ __('messages.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
