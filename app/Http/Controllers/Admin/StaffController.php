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
        $staffs = Staff::all();
        return view('admin.staff.index', compact('staffs'));
    }

    public function create()
    {
        return view('admin.staff.create');
    }

    public function store(Request $request)
    {
        $this->authorizeMaster($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account' => ['required', 'string', 'max:255', 'unique:staffs'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:staffs'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in([Staff::ROLE_MASTER, Staff::ROLE_ADMIN, Staff::ROLE_OBSERVER])],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        Staff::create($validated);

        return redirect()->route('admin.staff.index')->with('success', 'Staff member created successfully.');
    }

    public function edit(Staff $staff)
    {
        return view('admin.staff.edit', compact('staff'));
    }

    public function update(Request $request, Staff $staff)
    {
        $this->authorizeMaster($request);

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
        $this->authorizeMaster($request);

        if ($staff->id === $request->user()->id) {
            return redirect()->back()->with('error', 'Cannot delete yourself.');
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')->with('success', 'Staff member deleted successfully.');
    }

    protected function authorizeMaster(Request $request)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Only Master can perform this action.');
        }
    }
}
