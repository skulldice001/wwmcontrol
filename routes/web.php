<?php

use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\Admin\LibraryArticleController;
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
        // Lucky draw
        Route::get('events/lucky-draw/create', [EventController::class, 'createLuckyDraw'])->name('events.lucky_draw.create');
        Route::post('events/lucky-draw', [EventController::class, 'storeLuckyDraw'])->name('events.lucky_draw.store');
        Route::post('events/{event}/run-draw', [EventController::class, 'runDraw'])->name('events.run_draw');
        Route::get('events/{event}/draw-result', [EventController::class, 'luckyDrawResult'])->name('events.lucky_draw_result');
        Route::resource('events', EventController::class);
        Route::resource('users', UserController::class);
        Route::post('users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();
        Route::post('users/{user}/freeze-coins', [UserController::class, 'freezeCoins'])->name('users.freeze-coins');
        Route::post('users/{user}/adjust-coins', [UserController::class, 'adjustCoins'])->name('users.adjust-coins');
        Route::get('users/{user}/coin-history', [UserController::class, 'coinHistory'])->name('users.coin-history');
        Route::resource('library', LibraryArticleController::class);
        Route::post('library/{library}/publish',   [LibraryArticleController::class, 'publish'])->name('library.publish');
        Route::post('library/{library}/unpublish', [LibraryArticleController::class, 'unpublish'])->name('library.unpublish');
        Route::post('library-upload-image',        [LibraryArticleController::class, 'uploadImage'])->name('library.upload-image');
    });
});

use App\Http\Controllers\UserEventController;
use App\Http\Controllers\EntertainmentController;
use App\Http\Controllers\PokerGameController;
use App\Http\Controllers\BlackjackController;
use App\Http\Controllers\TaixiuController;
use App\Http\Controllers\LotteryController;
use App\Http\Controllers\BingoController;
use App\Http\Controllers\TienLenController;

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
    Route::post('/entertainment/poker/ai', [EntertainmentController::class, 'createAiPokerTable'])->name('entertainment.poker.ai.create');
    Route::get('/entertainment/poker/test-update', [EntertainmentController::class, 'testUpdate'])->name('entertainment.poker.test-update');
    Route::get('/entertainment/poker/{table}', [EntertainmentController::class, 'showTable'])->name('entertainment.poker.show')
        ->missing(fn () => redirect()->route('entertainment.poker'));
    Route::post('/entertainment/poker/{table}/join', [EntertainmentController::class, 'joinTable'])->name('entertainment.poker.join');
    Route::delete('/entertainment/poker/{table}/leave', [EntertainmentController::class, 'leaveTable'])->name('entertainment.poker.leave');
    Route::get('/entertainment/blackjack',                           [EntertainmentController::class, 'blackjack'])->name('entertainment.blackjack');
    Route::post('/entertainment/blackjack',                          [BlackjackController::class, 'createTable'])->name('entertainment.blackjack.create');
    Route::post('/entertainment/blackjack/ai',                       [BlackjackController::class, 'createAiTable'])->name('entertainment.blackjack.ai.create');
    Route::get('/entertainment/blackjack/{table}',                   [BlackjackController::class, 'show'])->name('entertainment.blackjack.show')
        ->missing(fn () => redirect()->route('entertainment.blackjack'));
    Route::post('/entertainment/blackjack/{table}/join',             [BlackjackController::class, 'joinTable'])->name('entertainment.blackjack.join');
    Route::delete('/entertainment/blackjack/{table}/leave',          [BlackjackController::class, 'leaveTable'])->name('entertainment.blackjack.leave');
    Route::post('/entertainment/blackjack/{table}/role',             [BlackjackController::class, 'chooseRole'])->name('entertainment.blackjack.role');
    Route::post('/entertainment/blackjack/{table}/ready',            [BlackjackController::class, 'ready'])->name('entertainment.blackjack.ready');
    Route::get('/entertainment/blackjack/{table}/game/state',        [BlackjackController::class, 'state'])->name('entertainment.blackjack.game.state');
    Route::post('/entertainment/blackjack/{table}/game/start',       [BlackjackController::class, 'startRound'])->name('entertainment.blackjack.game.start');
    Route::post('/entertainment/blackjack/{table}/game/deal',        [BlackjackController::class, 'deal'])->name('entertainment.blackjack.game.deal');
    Route::post('/entertainment/blackjack/{table}/game/action',      [BlackjackController::class, 'action'])->name('entertainment.blackjack.game.action');
    Route::post('/entertainment/blackjack/{table}/game/next',          [BlackjackController::class, 'nextRound'])->name('entertainment.blackjack.game.next');
    Route::post('/entertainment/blackjack/{table}/game/dealer-action', [BlackjackController::class, 'dealerAction'])->name('entertainment.blackjack.game.dealer-action');
    Route::get('/entertainment/blackjack/{table}/chat',                [BlackjackController::class, 'messages'])->name('entertainment.blackjack.chat.index');
    Route::post('/entertainment/blackjack/{table}/chat',               [BlackjackController::class, 'sendMessage'])->name('entertainment.blackjack.chat.send');

    // Poker game actions (inside a room)
    Route::post('/entertainment/poker/{table}/ready',        [PokerGameController::class, 'ready'])->name('entertainment.poker.ready');
    Route::post('/entertainment/poker/{table}/game/start',   [PokerGameController::class, 'start'])->name('entertainment.poker.game.start');
    Route::get('/entertainment/poker/{table}/game/state',    [PokerGameController::class, 'state'])->name('entertainment.poker.game.state');
    Route::post('/entertainment/poker/{table}/game/action',  [PokerGameController::class, 'action'])->name('entertainment.poker.game.action');
    Route::get('/entertainment/poker/{table}/chat',          [PokerGameController::class, 'messages'])->name('entertainment.poker.chat.index');
    Route::post('/entertainment/poker/{table}/chat',         [PokerGameController::class, 'sendMessage'])->name('entertainment.poker.chat.send');

    // Tài Xỉu
    Route::get('/entertainment/taixiu',                              [TaixiuController::class, 'index'])->name('entertainment.taixiu');
    Route::post('/entertainment/taixiu',                             [TaixiuController::class, 'createTable'])->name('entertainment.taixiu.create');
    Route::get('/entertainment/taixiu/{table}',                      [TaixiuController::class, 'show'])->name('entertainment.taixiu.show')
        ->missing(fn () => redirect()->route('entertainment.taixiu'));
    Route::post('/entertainment/taixiu/{table}/join',                [TaixiuController::class, 'joinTable'])->name('entertainment.taixiu.join');
    Route::delete('/entertainment/taixiu/{table}/leave',             [TaixiuController::class, 'leaveTable'])->name('entertainment.taixiu.leave');
    Route::get('/entertainment/taixiu/{table}/game/state',           [TaixiuController::class, 'state'])->name('entertainment.taixiu.game.state');
    Route::post('/entertainment/taixiu/{table}/game/bet',            [TaixiuController::class, 'bet'])->name('entertainment.taixiu.game.bet');
    Route::get('/entertainment/taixiu/{table}/chat',                 [TaixiuController::class, 'messages'])->name('entertainment.taixiu.chat.index');
    Route::post('/entertainment/taixiu/{table}/chat',                [TaixiuController::class, 'sendMessage'])->name('entertainment.taixiu.chat.send');

    // Bingo
    Route::get('/entertainment/bingo',                  [BingoController::class, 'index'])->name('entertainment.bingo');
    Route::post('/entertainment/bingo',                 [BingoController::class, 'createTable'])->name('entertainment.bingo.create');
    Route::get('/entertainment/bingo/{table}',          [BingoController::class, 'show'])->name('entertainment.bingo.show')
        ->missing(fn () => redirect()->route('entertainment.bingo'));
    Route::post('/entertainment/bingo/{table}/join',    [BingoController::class, 'joinTable'])->name('entertainment.bingo.join');
    Route::delete('/entertainment/bingo/{table}/leave', [BingoController::class, 'leaveTable'])->name('entertainment.bingo.leave');
    Route::post('/entertainment/bingo/{table}/ready',   [BingoController::class, 'ready'])->name('entertainment.bingo.ready');
    Route::get('/entertainment/bingo/{table}/state',    [BingoController::class, 'state'])->name('entertainment.bingo.state');

    // Lottery
    Route::get('/entertainment/lottery',        [LotteryController::class, 'index'])->name('entertainment.lottery');
    Route::get('/entertainment/lottery/state',  [LotteryController::class, 'state'])->name('entertainment.lottery.state');
    Route::post('/entertainment/lottery/ticket',[LotteryController::class, 'buyTicket'])->name('entertainment.lottery.ticket');
    Route::post('/entertainment/lottery/jackpot',[LotteryController::class, 'buyJackpotTicket'])->name('entertainment.lottery.jackpot');

    // Tiến Lên
    Route::get('/entertainment/tienlen',                       [TienLenController::class, 'index'])->name('entertainment.tienlen.index');
    Route::post('/entertainment/tienlen',                      [TienLenController::class, 'createTable'])->name('entertainment.tienlen.create');
    Route::post('/entertainment/tienlen/ai',                   [TienLenController::class, 'createAiTable'])->name('entertainment.tienlen.ai');
    Route::get('/entertainment/tienlen/{table}',               [TienLenController::class, 'room'])->name('entertainment.tienlen.room')
        ->missing(fn () => redirect()->route('entertainment.tienlen.index'));
    Route::post('/entertainment/tienlen/{table}/ready',        [TienLenController::class, 'ready'])->name('entertainment.tienlen.ready');
    Route::post('/entertainment/tienlen/{table}/start',        [TienLenController::class, 'start'])->name('entertainment.tienlen.start');
    Route::post('/entertainment/tienlen/{table}/play',         [TienLenController::class, 'play'])->name('entertainment.tienlen.play');
    Route::get('/entertainment/tienlen/{table}/state',         [TienLenController::class, 'state'])->name('entertainment.tienlen.state');
    Route::post('/entertainment/tienlen/{table}/chat',         [TienLenController::class, 'chat'])->name('entertainment.tienlen.chat');
    Route::delete('/entertainment/tienlen/{table}/leave',      [TienLenController::class, 'leave'])->name('entertainment.tienlen.leave');

    // Zoo-coin routes
    Route::post('/zoo-coins/transfer', [\App\Http\Controllers\ZooCoinController::class, 'transfer'])->name('zoo.transfer');
    Route::get('/zoo-coins/history', [\App\Http\Controllers\ZooCoinController::class, 'history'])->name('zoo.history');

    // Library Routes
    Route::get('/library',                        [LibraryController::class, 'index'])->name('library.index');
    Route::get('/library/{category}',             [LibraryController::class, 'category'])->name('library.category');
    Route::get('/library/{category}/{article}',   [LibraryController::class, 'show'])->name('library.show');
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
