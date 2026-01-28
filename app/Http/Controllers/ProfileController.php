<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
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

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function updateInnerWays(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'inner_ways' => 'required|array',
            'inner_ways.*.slug' => 'required|string|exists:inner_ways,slug',
            'inner_ways.*.level' => 'required|integer|min:1|max:6',
            'main_skill_id' => 'nullable|exists:skills,id',
            'sub_skill_id' => 'nullable|exists:skills,id',
        ]);

        // Constraint: main and sub skills must be different
        if ($request->filled('main_skill_id') && $request->filled('sub_skill_id') && $request->main_skill_id == $request->sub_skill_id) {
            return response()->json(['message' => 'Võ công chính và phụ không được trùng nhau.'], 422);
        }

        $updateData = [];
        if ($request->has('main_skill_id')) {
            $updateData['main_skill_id'] = $validated['main_skill_id'];
        }
        if ($request->has('sub_skill_id')) {
            $updateData['sub_skill_id'] = $validated['sub_skill_id'];
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        $syncData = [];
        foreach ($validated['inner_ways'] as $iw) {
            $innerWay = \App\Models\InnerWay::where('slug', $iw['slug'])->first();
            if ($innerWay) {
                $syncData[$innerWay->id] = ['level' => $iw['level']];
            }
        }

        $user->innerWays()->sync($syncData);

        $colorOrder = ['gold', 'purple', 'blue'];
        $innerWays = $user->innerWays()
            ->get()
            ->sort(function ($a, $b) use ($colorOrder) {
                $indexA = array_search($a->color, $colorOrder);
                $indexB = array_search($b->color, $colorOrder);

                $indexA = $indexA === false ? 999 : $indexA;
                $indexB = $indexB === false ? 999 : $indexB;

                if ($indexA !== $indexB) {
                    return $indexA <=> $indexB;
                }

                if ($a->pivot->level !== $b->pivot->level) {
                    return $b->pivot->level <=> $a->pivot->level;
                }

                return strcasecmp($a->name, $b->name);
            })
            ->values();

        return response()->json([
            'message' => 'Cập nhật võ công thành công',
            'inner_ways' => $innerWays->map(function($iw) {
                return [
                    'name' => $iw->name,
                    'slug' => $iw->slug,
                    'icon' => $iw->icon,
                    'color' => $iw->color,
                    'level' => $iw->pivot->level
                ];
            }),
            'user' => $user->fresh(['mainSkill', 'subSkill'])
        ]);
    }

    public function getSkills()
    {
        return response()->json([
            'skills' => \App\Models\Skill::all()
        ]);
    }
}
