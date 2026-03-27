<?php

namespace App\Http\Controllers;

use App\Events\TaixiuRoomUpdated;
use App\Events\TaixiuTableUpdated;
use App\Jobs\TaixiuAutoRollJob;
use App\Models\TaixiuMessage;
use App\Models\TaixiuTable;
use App\Services\Taixiu\TaixiuEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaixiuController extends Controller
{
    /** GET /entertainment/taixiu — lobby */
    public function index()
    {
        $tables = TaixiuTable::all();

        if (request()->expectsJson()) {
            return response()->json(['tables' => $tables->values()]);
        }

        return view('entertainment.taixiu', compact('tables'));
    }

    /** POST /entertainment/taixiu — create table */
    public function createTable(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:60',
            'min_bet'     => 'required|integer|min:1',
            'max_bet'     => 'required|integer|min:1',
            'max_players' => 'required|integer|min:2|max:100',
        ]);

        $data['status']          = 'waiting';
        $data['current_players'] = 0;

        $table = TaixiuTable::create($data);

        $user = Auth::user();
        $table->players()->attach($user->id, ['joined_at' => now()]);
        $table->current_players = 1;
        $table->status          = 'playing';
        $table->save();

        event(new TaixiuTableUpdated($table));

        // Start first round
        $game  = TaixiuEngine::startRound($table);
        TaixiuAutoRollJob::dispatch($table->id, $game->id, $game->state['bet_deadline_at'])
            ->delay(now()->addSeconds(TaixiuEngine::BETTING_DURATION));

        return response()->json(['redirect' => route('entertainment.taixiu.show', $table)]);
    }

    /** GET /entertainment/taixiu/{table} — game room */
    public function show(TaixiuTable $table)
    {
        return view('entertainment.taixiu_room', compact('table'));
    }

    /** POST /entertainment/taixiu/{table}/join */
    public function joinTable(TaixiuTable $table)
    {
        $user = Auth::user();

        if ($table->players()->where('user_id', $user->id)->exists()) {
            return response()->json(['redirect' => route('entertainment.taixiu.show', $table)]);
        }

        // Remove from other taixiu table
        $other = TaixiuTable::whereHas('players', fn($q) => $q->where('user_id', $user->id))
            ->where('id', '!=', $table->id)
            ->first();

        if ($other) {
            $other->players()->detach($user->id);
            $other->current_players = $other->players()->count();
            $other->status = $other->current_players > 0 ? 'playing' : 'waiting';
            $other->save();
            event(new TaixiuTableUpdated($other));
        }

        if ($table->current_players >= $table->max_players) {
            return response()->json(['message' => 'Bàn đã đầy.'], 422);
        }

        try {
            $table->players()->attach($user->id, ['joined_at' => now()]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return response()->json(['redirect' => route('entertainment.taixiu.show', $table)]);
        }

        $table->current_players = $table->players()->count();
        $table->status          = 'playing';
        $table->save();

        event(new TaixiuTableUpdated($table));

        // Start a round if none is running (first player scenario)
        if (!$table->activeGame()) {
            $game = TaixiuEngine::startRound($table);
            TaixiuAutoRollJob::dispatch($table->id, $game->id, $game->state['bet_deadline_at'])
                ->delay(now()->addSeconds(TaixiuEngine::BETTING_DURATION));
        }

        return response()->json(['redirect' => route('entertainment.taixiu.show', $table)]);
    }

    /** DELETE /entertainment/taixiu/{table}/leave */
    public function leaveTable(TaixiuTable $table)
    {
        $user = Auth::user();

        $table->players()->detach($user->id);
        $table->current_players = $table->players()->count();

        if ($table->current_players <= 0) {
            event(new TaixiuTableUpdated($table->fill(['status' => 'closed'])));
            $table->delete();
            return redirect()->route('entertainment.taixiu');
        }

        $table->status = 'playing';
        $table->save();
        event(new TaixiuTableUpdated($table));

        return redirect()->route('entertainment.taixiu');
    }

    /** GET /entertainment/taixiu/{table}/game/state */
    public function state(TaixiuTable $table)
    {
        $game = $table->activeGame();
        $user = Auth::user();
        $avail = $user->z_coins - $user->z_coins_frozen;

        if (!$game) {
            return response()->json([
                'game'    => null,
                'z_coins' => $avail,
                'table'   => [
                    'min_bet' => $table->min_bet,
                    'max_bet' => $table->max_bet,
                ],
            ]);
        }

        return response()->json([
            'game'    => TaixiuEngine::clientState($game, $user->id),
            'z_coins' => $avail,
            'table'   => [
                'min_bet' => $table->min_bet,
                'max_bet' => $table->max_bet,
            ],
        ]);
    }

    /** POST /entertainment/taixiu/{table}/game/bet */
    public function bet(Request $request, TaixiuTable $table)
    {
        $request->validate([
            'choice' => 'required|in:tai,xiu',
            'amount' => 'required|integer|min:1',
        ]);

        $game = $table->activeGame();
        if (!$game) {
            return response()->json(['error' => 'Không có ván đang chơi.'], 422);
        }

        $result = TaixiuEngine::placeBet(
            $game,
            Auth::id(),
            $request->choice,
            (int) $request->amount,
            $table
        );

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        $updatedGame = $result['game'];
        $state       = TaixiuEngine::clientState($updatedGame);

        event(new TaixiuRoomUpdated($table->id, [
            'type'  => 'bet_placed',
            'state' => $state,
        ]));

        return response()->json([
            'state' => TaixiuEngine::clientState($updatedGame, Auth::id()),
        ]);
    }

    // ── Chat ─────────────────────────────────────────────────────────────────

    /** GET {table}/chat */
    public function messages(TaixiuTable $table)
    {
        $messages = TaixiuMessage::where('table_id', $table->id)
            ->with('user:id,name,discord_avatar')
            ->latest()
            ->take(100)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($m) => [
                'id'      => $m->id,
                'user_id' => $m->user_id,
                'name'    => $m->user?->name ?? 'Unknown',
                'avatar'  => $m->user?->discord_avatar ?? null,
                'message' => $m->message,
                'time'    => $m->created_at->format('H:i'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    /** POST {table}/chat */
    public function sendMessage(Request $request, TaixiuTable $table)
    {
        $request->validate(['message' => 'required|string|max:500']);

        $user = Auth::user();

        if (!$table->players()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Not at table'], 403);
        }

        $msg = TaixiuMessage::create([
            'table_id' => $table->id,
            'user_id'  => $user->id,
            'message'  => $request->message,
        ]);

        $payload = [
            'type'         => 'chat_message',
            'chat_message' => [
                'id'      => $msg->id,
                'user_id' => $user->id,
                'name'    => $user->name,
                'avatar'  => $user->discord_avatar ?? null,
                'message' => $msg->message,
                'time'    => $msg->created_at->format('H:i'),
            ],
        ];

        event(new TaixiuRoomUpdated($table->id, $payload));

        return response()->json(['message' => $payload['chat_message']]);
    }
}
