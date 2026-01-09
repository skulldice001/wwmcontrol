<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Services\DiscordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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

use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\EventController;

Route::get('/admin/user', function (Request $request) {
    return $request->user();
})->middleware('auth:staff');

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
