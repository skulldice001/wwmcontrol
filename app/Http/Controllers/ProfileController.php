<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Skill;
use App\Models\InnerWay;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'country' => 'nullable|string|max:255',
            'online_from' => 'nullable|string|max:10',
            'online_to' => 'nullable|string|max:10',
            'ingame_name' => 'nullable|string|max:255',
            'ingame_id' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function editSkills()
    {
        $user = Auth::user();
        $skills = Skill::all();
        $innerWays = InnerWay::all();

        // Load user's current inner ways with pivot data
        $user->load('innerWays');

        return view('skills.edit', compact('user', 'skills', 'innerWays'));
    }

    public function updateInnerWays(Request $request)
    {
        $user = Auth::user();

        // Validate for Blade form structure: inner_ways[slug] = level
        $validated = $request->validate([
             'inner_ways' => 'array',
             'inner_ways.*' => 'integer|min:0|max:6', // Key is slug, value is level
             'main_skill_id' => 'nullable|exists:skills,id',
             'sub_skill_id' => 'nullable|exists:skills,id',
        ]);

        // Update Skills
        if ($request->filled('main_skill_id') && $request->filled('sub_skill_id') && $request->main_skill_id == $request->sub_skill_id) {
             return redirect()->back()->withErrors(['sub_skill_id' => 'Võ công chính và phụ không được trùng nhau.']);
        }

        $user->main_skill_id = $request->input('main_skill_id');
        $user->sub_skill_id = $request->input('sub_skill_id');
        $user->save();

        // Update Inner Ways
        if ($request->has('inner_ways')) {
            $syncData = [];
            foreach ($request->input('inner_ways') as $slug => $level) {
                if ($level > 0) {
                    $innerWay = InnerWay::where('slug', $slug)->first();
                    if ($innerWay) {
                        $syncData[$innerWay->id] = ['level' => $level];
                    }
                }
            }
            $user->innerWays()->sync($syncData);
        }

        return redirect()->back()->with('success', 'Cập nhật võ công thành công');
    }
}
