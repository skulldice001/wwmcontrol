<?php

use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('http://localhost:3000');
});

Route::get('/login', function () {
    return redirect(config('app.frontend_url') . '/admin/login');
})->name('login');

// Staff Routes
Route::get('/admin', function () {
    return redirect(config('app.frontend_url') . '/admin/login');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', function () {
        return redirect(config('app.frontend_url') . '/admin/login');
    })->name('login');

    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    Route::middleware('auth:staff')->group(function () {
        Route::get('/dashboard', function () {
            return redirect(config('app.frontend_url') . '/admin/dashboard');
        })->name('dashboard');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/auth/discord', [DiscordController::class, 'redirect'])->name('auth.discord');
Route::get('/auth/discord/callback', [DiscordController::class, 'callback']);

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->noContent();
})->middleware('auth');

