<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        $rules = [
            'country' => 'nullable|string|max:255',
            'online_from' => 'nullable|date_format:H:i',
            'online_to' => 'nullable|date_format:H:i',
            'ingame_name' => 'nullable|string|max:255',
            'ingame_id' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        // Allow name and email update if not linked to Discord
        if (!$user->discord_id) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|string|email|max:255|unique:users,email,' . $user->id;
            $rules['account'] = 'nullable|string|max:255|unique:users,account,' . $user->id;
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return redirect()->back()->with('success', __('messages.profile_updated_success'));
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
        ], [
            'inner_ways.*.max' => __('messages.inner_way_level_max_error'),
            'inner_ways.*.min' => __('messages.inner_way_level_min_error'),
            'inner_ways.*.integer' => __('messages.inner_way_level_integer_error'),
        ]);

        // Update Skills
        if ($request->filled('main_skill_id') && $request->filled('sub_skill_id') && $request->main_skill_id == $request->sub_skill_id) {
             return redirect()->back()->withErrors(['sub_skill_id' => __('messages.skills_duplicate_error')]);
        }

        $user->main_skill_id = $request->input('main_skill_id');
        $user->sub_skill_id = $request->input('sub_skill_id');
        $user->save();

        // Update Inner Ways
        if ($request->has('inner_ways')) {
            $syncData = [];
            foreach ($request->input('inner_ways') as $slug => $level) {
                if ($level >= 0) {
                    $innerWay = InnerWay::where('slug', $slug)->first();
                    if ($innerWay) {
                        $syncData[$innerWay->id] = ['level' => $level];
                    }
                }
            }
            $user->innerWays()->sync($syncData);
        }

        return redirect()->back()->with('success', __('messages.skills_updated_success'));
    }

    public function updateTheme(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'navbar_variant' => 'nullable|string',
            'sidebar_variant' => 'nullable|string',
            'brand_logo_variant' => 'nullable|string',
            'accent_color' => 'nullable|string',
            'background_color' => 'nullable|string',
        ]);

        $data = $request->only(['navbar_variant', 'sidebar_variant', 'brand_logo_variant', 'accent_color', 'background_color']);
        $data['dark_mode'] = $request->has('dark_mode');

        $user->themeSetting()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return redirect()->back()->with('success', __('messages.theme_updated_success'));
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        // Only require current password if user actually has one
        if ($user->password) {
            $rules['current_password'] = ['required'];
        }

        $request->validate($rules);

        if ($user->password && !Hash::check($request->current_password, $user->password)) {
             return back()->withErrors(['current_password' => __('messages.current_password_incorrect')]);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', __('messages.password_updated_success'));
    }
}
