<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Get all users with their inner ways and skills
        $users = User::with(['innerWays', 'mainSkill', 'subSkill'])->get();
        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['innerWays', 'mainSkill', 'subSkill']);
        return view('admin.users.show', compact('user'));
    }
}
