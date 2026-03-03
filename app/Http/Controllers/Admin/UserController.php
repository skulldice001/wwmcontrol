<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        // Get all users with their inner ways and skills
        $users = User::with(['innerWays', 'mainSkill', 'subSkill'])->get();
        return view('admin.users.index', compact('users'));
    }

    public function destroy(User $user)
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', __('messages.user_deleted_success'));
    }

    public function show(User $user)
    {
        $user->load(['innerWays', 'mainSkill', 'subSkill']);
        return view('admin.users.show', compact('user'));
    }

    public function create()
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'account' => ['required', 'string', 'max:255', 'unique:'.User::class],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'ingame_name' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'account' => $request->account,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'ingame_name' => $request->ingame_name,
            'country' => $request->country,
        ]);

        return redirect()->route('admin.users.index')->with('success', __('messages.user_created_success'));
    }
}
