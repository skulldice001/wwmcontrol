@extends('layouts.admin')

@section('title', __('messages.create_new_staff'))

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.create_new_staff') }}</h3>
    </div>
    <form action="{{ route('admin.staff.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label>{{ __('messages.name') }}</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ __('messages.enter_name') }}">
                @error('name') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.account') }}</label>
                <input type="text" name="account" class="form-control @error('account') is-invalid @enderror" value="{{ old('account') }}" placeholder="{{ __('messages.enter_account') }}">
                @error('account') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.email') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="{{ __('messages.enter_email') }}">
                @error('email') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.password') }}</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('messages.enter_password') }}">
                @error('password') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.role') }}</label>
                <select name="role" class="form-control @error('role') is-invalid @enderror">
                    <option value="master" {{ old('role') == 'master' ? 'selected' : '' }}>Master</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="observer" {{ old('role') == 'observer' ? 'selected' : '' }}>Observer</option>
                </select>
                @error('role') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('messages.create') }}</button>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-default float-right">{{ __('messages.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
