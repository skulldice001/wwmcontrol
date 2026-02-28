<?php

use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('lang/{locale}', function ($locale) {
    if (! in_array($locale, ['en', 'vi'])) {
        abort(400);
    }
    session(['locale' => $locale]);
    return redirect()->back();
})->name('lang.switch');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Staff Routes
Route::get('/admin', function () {
    return redirect()->route('admin.dashboard');
});

use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StaffProfileController;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

    Route::middleware('auth:staff')->group(function () {
        Route::get('/dashboard', function () {
            $membersCount = \App\Models\User::count();

            $eventsRunning = \App\Models\Event::whereIn('status', ['upcoming', 'ongoing'])->count();
            $eventsCompleted = \App\Models\Event::where('status', 'completed')->count();

            $roles = \App\Models\User::with('mainSkill')->get()
                ->groupBy(function ($user) {
                    return \App\Constants\SkillRole::getRole(optional($user->mainSkill)->slug);
                })
                ->map->count();

            return view('admin.dashboard', [
                'membersCount' => $membersCount,
                'eventsRunning' => $eventsRunning,
                'eventsCompleted' => $eventsCompleted,
                'roles' => $roles,
            ]);
        })->name('dashboard');

        Route::resource('staff', StaffController::class);
        Route::get('/profile', [StaffProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [StaffProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [StaffProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::get('events/{event}/participants', [EventController::class, 'participants'])->name('events.participants');
        Route::get('events/{event}/formation', [EventController::class, 'formation'])->name('events.formation');
        Route::post('events/{event}/formation', [EventController::class, 'saveFormation'])->name('events.formation.save');
        Route::post('events/{event}/complete', [EventController::class, 'complete'])->name('events.complete');
        Route::resource('events', EventController::class);
        Route::resource('users', UserController::class);
    });
});

use App\Http\Controllers\UserEventController;
use App\Http\Controllers\EntertainmentController;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::put('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme.update');
    Route::get('/skills', [ProfileController::class, 'editSkills'])->name('skills.edit');
    Route::put('/skills', [ProfileController::class, 'updateInnerWays'])->name('skills.update');

    // User Events Routes
    Route::get('/events', [UserEventController::class, 'index'])->name('events.index');
    Route::post('/events/{event}/register', [UserEventController::class, 'register'])->name('events.register');
    Route::delete('/events/{event}/unregister', [UserEventController::class, 'unregister'])->name('events.unregister');
    Route::get('/events/{event}/map', [UserEventController::class, 'map'])->name('events.map');

    // Entertainment Routes
    Route::get('/entertainment', [EntertainmentController::class, 'index'])->name('entertainment.index');
    Route::get('/entertainment/poker', [EntertainmentController::class, 'poker'])->name('entertainment.poker');
    Route::get('/entertainment/blackjack', [EntertainmentController::class, 'blackjack'])->name('entertainment.blackjack');
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
    return redirect()->route('login');
})->middleware('auth')->name('logout');
