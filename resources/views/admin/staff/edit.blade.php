@extends('layouts.admin')

@section('title', __('messages.edit_staff'))

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.edit_staff') }}: {{ $staff->name }}</h3>
    </div>
    <form action="{{ route('admin.staff.update', $staff->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card-body">
            <div class="form-group">
                <label>{{ __('messages.name') }}</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $staff->name) }}" placeholder="{{ __('messages.enter_name') }}">
                @error('name') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.account') }}</label>
                <input type="text" name="account" class="form-control @error('account') is-invalid @enderror" value="{{ old('account', $staff->account) }}" placeholder="{{ __('messages.enter_account') }}">
                @error('account') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.email') }}</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $staff->email) }}" placeholder="{{ __('messages.enter_email') }}">
                @error('email') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.password') }} ({{ __('messages.leave_blank_password') }})</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="{{ __('messages.enter_new_password') }}">
                @error('password') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>{{ __('messages.role') }}</label>
                <select name="role" class="form-control @error('role') is-invalid @enderror">
                    <option value="master"    {{ old('role', $staff->role) == 'master'    ? 'selected' : '' }}>{{ __('messages.role_master') }}</option>
                    <option value="admin"     {{ old('role', $staff->role) == 'admin'     ? 'selected' : '' }}>{{ __('messages.role_admin') }}</option>
                    <option value="observer"  {{ old('role', $staff->role) == 'observer'  ? 'selected' : '' }}>{{ __('messages.role_observer') }}</option>
                    <option value="librarian" {{ old('role', $staff->role) == 'librarian' ? 'selected' : '' }}>{{ __('messages.role_librarian') }}</option>
                </select>
                @error('role') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('messages.update') }}</button>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-default float-right">{{ __('messages.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
