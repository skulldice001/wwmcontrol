<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffProfileController extends Controller
{
    public function edit(Request $request)
    {
        $staff = $request->user();

        return view('admin.staff.profile', compact('staff'));
    }

    public function update(Request $request)
    {
        $staff = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:staffs,email,' . $staff->id],
        ]);

        $staff->update($validated);

        return redirect()->route('admin.profile.edit')->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $staff = $request->user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $staff->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $staff->password = Hash::make($validated['password']);
        $staff->save();

        return redirect()->route('admin.profile.edit')->with('success', 'Password updated successfully.');
    }
}
