<?php

use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LibraryController;
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
        Route::post('staff/{staff}/restore', [StaffController::class, 'restore'])->name('staff.restore')->withTrashed();
        Route::get('/profile', [StaffProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [StaffProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [StaffProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::get('events/{event}/participants', [EventController::class, 'participants'])->name('events.participants');
        Route::get('events/{event}/add-participants', [EventController::class, 'addParticipantsForm'])->name('events.add_participants_form');
        Route::post('events/{event}/add-participants', [EventController::class, 'addParticipants'])->name('events.add_participants');
        Route::get('events/{event}/formation', [EventController::class, 'formation'])->name('events.formation');
        Route::post('events/{event}/formation', [EventController::class, 'saveFormation'])->name('events.formation.save');
        Route::post('events/{event}/complete', [EventController::class, 'complete'])->name('events.complete');
        Route::resource('events', EventController::class);
        Route::resource('users', UserController::class);
        Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
        Route::post('users/{user}/freeze-coins', [UserController::class, 'freezeCoins'])->name('users.freeze-coins');
        Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    });
});

use App\Http\Controllers\UserEventController;
use App\Http\Controllers\EntertainmentController;
use App\Http\Controllers\PokerGameController;
use App\Http\Controllers\BlackjackController;

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
    Route::get('/entertainment/poker',  [EntertainmentController::class, 'poker'])->name('entertainment.poker');
    Route::post('/entertainment/poker', [EntertainmentController::class, 'createTable'])->name('entertainment.poker.create');
    Route::get('/entertainment/poker/test-update', [EntertainmentController::class, 'testUpdate'])->name('entertainment.poker.test-update');
    Route::get('/entertainment/poker/{table}', [EntertainmentController::class, 'showTable'])->name('entertainment.poker.show');
    Route::post('/entertainment/poker/{table}/join', [EntertainmentController::class, 'joinTable'])->name('entertainment.poker.join');
    Route::delete('/entertainment/poker/{table}/leave', [EntertainmentController::class, 'leaveTable'])->name('entertainment.poker.leave');
    Route::get('/entertainment/blackjack',                           [EntertainmentController::class, 'blackjack'])->name('entertainment.blackjack');
    Route::post('/entertainment/blackjack',                          [BlackjackController::class, 'createTable'])->name('entertainment.blackjack.create');
    Route::get('/entertainment/blackjack/{table}',                   [BlackjackController::class, 'show'])->name('entertainment.blackjack.show');
    Route::post('/entertainment/blackjack/{table}/join',             [BlackjackController::class, 'joinTable'])->name('entertainment.blackjack.join');
    Route::delete('/entertainment/blackjack/{table}/leave',          [BlackjackController::class, 'leaveTable'])->name('entertainment.blackjack.leave');
    Route::post('/entertainment/blackjack/{table}/ready',            [BlackjackController::class, 'ready'])->name('entertainment.blackjack.ready');
    Route::get('/entertainment/blackjack/{table}/game/state',        [BlackjackController::class, 'state'])->name('entertainment.blackjack.game.state');
    Route::post('/entertainment/blackjack/{table}/game/deal',        [BlackjackController::class, 'deal'])->name('entertainment.blackjack.game.deal');
    Route::post('/entertainment/blackjack/{table}/game/action',      [BlackjackController::class, 'action'])->name('entertainment.blackjack.game.action');

    // Poker game actions (inside a room)
    Route::post('/entertainment/poker/{table}/ready',        [PokerGameController::class, 'ready'])->name('entertainment.poker.ready');
    Route::post('/entertainment/poker/{table}/game/start',   [PokerGameController::class, 'start'])->name('entertainment.poker.game.start');
    Route::get('/entertainment/poker/{table}/game/state',    [PokerGameController::class, 'state'])->name('entertainment.poker.game.state');
    Route::post('/entertainment/poker/{table}/game/action',  [PokerGameController::class, 'action'])->name('entertainment.poker.game.action');

    // Library Routes
    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
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
