@extends('layouts.admin')

@section('title', __('messages.staff_management'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('messages.staff_list') }}</h3>
        <div class="card-tools">
            <a href="{{ route('admin.staff.create') }}" class="btn btn-primary btn-sm">{{ __('messages.add_new_staff') }}</a>
        </div>
    </div>
        <div class="card-body">
        @php
            $currentStaff = Auth::guard('staff')->user();
        @endphp
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
                    <th>{{ __('messages.name') }}</th>
                    <th>{{ __('messages.account') }}</th>
                    <th>{{ __('messages.email') }}</th>
                    <th>{{ __('messages.role') }}</th>
                    <th>{{ __('messages.actions') }}</th>
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
                        @if($currentStaff && $currentStaff->canManage($staff))
                            <a href="{{ route('admin.staff.edit', $staff->id) }}" class="btn btn-info btn-sm">{{ __('messages.edit') }}</a>
                            @if($staff->id !== $currentStaff->id)
                                <form action="{{ route('admin.staff.destroy', $staff->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('messages.confirm_delete') }}')">{{ __('messages.delete') }}</button>
                                </form>
                            @endif
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
