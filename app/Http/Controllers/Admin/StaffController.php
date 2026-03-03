<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staffs = Staff::withTrashed()->get();
        return view('admin.staff.index', compact('staffs'));
    }

    public function restore(Request $request, Staff $staff)
    {
        $this->authorizeAdmin($request);

        if (!$request->user()->canManage($staff)) {
            abort(403);
        }

        $staff->restore();

        return redirect()->route('admin.staff.index')->with('success', __('messages.staff_enabled_success'));
    }

    public function create()
    {
        return view('admin.staff.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        $current = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account' => ['required', 'string', 'max:255', 'unique:staffs'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:staffs'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in([Staff::ROLE_MASTER, Staff::ROLE_ADMIN, Staff::ROLE_OBSERVER])],
        ]);

        if (!$current->isMaster() && $validated['role'] === Staff::ROLE_MASTER) {
            return redirect()->back()->with('error', 'Only Master can create another Master.');
        }

        $validated['password'] = Hash::make($validated['password']);

        Staff::create($validated);

        return redirect()->route('admin.staff.create')->with('success', 'Staff member created successfully. Click button below to return to list.');
    }

    public function edit(Staff $staff)
    {
        if (!auth('staff')->user()->canManage($staff)) {
            abort(403);
        }

        return view('admin.staff.edit', compact('staff'));
    }

    public function update(Request $request, Staff $staff)
    {
        $this->authorizeAdmin($request);

        if (!$request->user()->canManage($staff)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['string', 'max:255'],
            'account' => ['string', 'max:255', Rule::unique('staffs')->ignore($staff->id)],
            'email' => ['string', 'email', 'max:255', Rule::unique('staffs')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => [Rule::in([Staff::ROLE_MASTER, Staff::ROLE_ADMIN, Staff::ROLE_OBSERVER])],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $staff->update($validated);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(Request $request, Staff $staff)
    {
        $this->authorizeAdmin($request);

        if ($staff->id === $request->user()->id) {
            return redirect()->back()->with('error', 'Cannot delete yourself.');
        }

        if (!$request->user()->canManage($staff)) {
            abort(403);
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', __('messages.staff_disabled_success'));
    }

    protected function authorizeAdmin(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Only Master or Admin can perform this action.');
        }
    }
}
