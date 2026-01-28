<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Services\DiscordService;
use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user()->load(['mainSkill', 'subSkill']);
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/skills', [ProfileController::class, 'getSkills']);

Route::middleware('auth:sanctum')->get('/user/inner-ways', function (Request $request) {
    $colorOrder = ['gold', 'purple', 'blue'];

    $innerWays = $request->user()->innerWays()
        ->get()
        ->sort(function ($a, $b) use ($colorOrder) {
            $indexA = array_search($a->color, $colorOrder);
            $indexB = array_search($b->color, $colorOrder);

            $indexA = $indexA === false ? 999 : $indexA;
            $indexB = $indexB === false ? 999 : $indexB;

            if ($indexA !== $indexB) {
                return $indexA <=> $indexB;
            }

            // If same color, sort by level descending
            if ($a->pivot->level !== $b->pivot->level) {
                return $b->pivot->level <=> $a->pivot->level;
            }

            // If same color and same level, sort by name ascending
            return strcasecmp($a->name, $b->name);
        })
        ->values();

    return response()->json([
        'inner_ways' => $innerWays->map(function($iw) {
            return [
                'name' => $iw->name,
                'slug' => $iw->slug,
                'icon' => $iw->icon,
                'color' => $iw->color,
                'level' => $iw->pivot->level
            ];
        })
    ]);
});

Route::get('/discord/check-role', function (Request $request, DiscordService $discordService) {
    $user = $request->user();
    if (!$user->discord_id) {
        return response()->json(['error' => 'User does not have a connected Discord account.'], 400);
    }

    $hasRole = $discordService->hasRole($user->discord_id);

    return response()->json([
        'has_role' => $hasRole,
    ]);
})->middleware('auth:sanctum');

Route::get('/discord/roles', function (Request $request, DiscordService $discordService) {
    $user = $request->user();
    if (!$user->discord_id) {
        return response()->json(['error' => 'User does not have a connected Discord account.'], 400);
    }

    $roles = $discordService->getRoles($user->discord_id);

    return response()->json([
        'roles' => $roles,
    ]);
})->middleware('auth:sanctum');

Route::get('/admin/user', function (Request $request) {
    return $request->user();
})->middleware('auth:staff');

Route::middleware('auth:sanctum')->post('/user/profile', [ProfileController::class, 'update']);
Route::middleware('auth:sanctum')->post('/user/inner-ways', [ProfileController::class, 'updateInnerWays']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/events', [\App\Http\Controllers\UserEventController::class, 'index']);
    Route::post('/events/{event}/register', [\App\Http\Controllers\UserEventController::class, 'register']);
    Route::post('/events/{event}/unregister', [\App\Http\Controllers\UserEventController::class, 'unregister']);
});

Route::prefix('admin')->middleware('auth:staff')->group(function () {
    Route::apiResource('staffs', StaffController::class);
    Route::apiResource('events', EventController::class);
});

Route::post('/admin/login', [LoginController::class, 'login']);

Route::post('/admin/logout', [LoginController::class, 'logout'])->middleware('auth:staff');

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->noContent();
})->middleware('auth:sanctum');
