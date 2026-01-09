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
        return response()->json(Staff::all());
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

        $staff = Staff::create($validated);

        return response()->json($staff, 201);
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

        return response()->json($staff);
    }

    public function destroy(Request $request, Staff $staff)
    {
        $this->authorizeMaster($request);

        if ($staff->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete yourself.'], 400);
        }

        $staff->delete();

        return response()->json(null, 204);
    }

    protected function authorizeMaster(Request $request)
    {
        if (!$request->user()->isMaster()) {
            abort(403, 'Only Master can perform this action.');
        }
    }
}
