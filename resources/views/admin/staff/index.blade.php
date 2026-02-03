@extends('layouts.admin')

@section('title', 'Staff Management')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Staff List</h3>
        <div class="card-tools">
            <a href="{{ route('admin.staff.create') }}" class="btn btn-primary btn-sm">Add New Staff</a>
        </div>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Account</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffs as $staff)
                <tr>
                    <td>{{ $staff->id }}</td>
                    <td>{{ $staff->name }}</td>
                    <td>{{ $staff->account }}</td>
                    <td>{{ $staff->email }}</td>
                    <td>
                        <span class="badge badge-{{ $staff->role == 'master' ? 'danger' : ($staff->role == 'admin' ? 'primary' : 'secondary') }}">
                            {{ ucfirst($staff->role) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.staff.edit', $staff->id) }}" class="btn btn-info btn-sm">Edit</a>
                        @if($staff->id !== Auth::user()->id)
                            <form action="{{ route('admin.staff.destroy', $staff->id) }}" method="POST" style="display:inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
