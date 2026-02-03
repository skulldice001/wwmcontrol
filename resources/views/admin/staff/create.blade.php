@extends('layouts.admin')

@section('title', 'Create Staff')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Create New Staff</h3>
    </div>
    <form action="{{ route('admin.staff.store') }}" method="POST">
        @csrf
        <div class="card-body">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Enter name">
                @error('name') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Account</label>
                <input type="text" name="account" class="form-control @error('account') is-invalid @enderror" value="{{ old('account') }}" placeholder="Enter account">
                @error('account') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="Enter email">
                @error('email') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter password">
                @error('password') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control @error('role') is-invalid @enderror">
                    <option value="master" {{ old('role') == 'master' ? 'selected' : '' }}>Master</option>
                    <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="observer" {{ old('role') == 'observer' ? 'selected' : '' }}>Observer</option>
                </select>
                @error('role') <span class="error invalid-feedback">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Create</button>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-default float-right">Cancel</a>
        </div>
    </form>
</div>
@endsection
