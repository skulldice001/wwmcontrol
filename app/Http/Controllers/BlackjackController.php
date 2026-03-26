<?php

namespace App\Http\Controllers;

use App\Events\BlackjackRoomUpdated;
use App\Events\BlackjackTableUpdated;
use App\Models\BlackjackGame;
use App\Models\BlackjackTable;
use App\Services\Blackjack\BlackjackEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BlackjackController extends Controller
{
    // ── Table management ──────────────────────────────────────────────────

    public function createTable(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:60',
            'min_bet'     => 'required|integer|min:1',
            'max_bet'     => 'required|integer|min:1|gte:min_bet',
            'max_players' => 'required|integer|min:2|max:7',
        ]);

        $data['status']          = 'waiting';
        $data['current_players'] = 0;
        $data['is_preset']       = false;

        $table = BlackjackTable::create($data);

        // Auto-join creator
        $user = Auth::user();
        $table->players()->attach($user->id, ['joined_at' => now()]);
        $table->current_players = 1;
        $table->status          = 'playing';
        $table->save();

        event(new BlackjackTableUpdated($table));

        return response()->json(['redirect' => route('entertainment.blackjack.show', $table)]);
    }

    public function joinTable(BlackjackTable $table)
    {
        $user = Auth::user();

        // Already at this table → redirect in
        if ($table->players()->where('user_id', $user->id)->exists()) {
            return response()->json(['redirect' => route('entertainment.blackjack.show', $table)]);
        }

        // Remove from any other blackjack table first
        $other = BlackjackTable::whereHas('players', fn($q) => $q->where('user_id', $user->id))
            ->where('id', '!=', $table->id)
            ->first();

        if ($other) {
            $other->players()->detach($user->id);
            $other->current_players = $other->players()->count();
            $other->status          = $other->current_players <= 0 ? 'waiting' : 'playing';
            $other->save();
            event(new BlackjackTableUpdated($other));
        }

        if ($table->current_players >= $table->max_players) {
            return response()->json(['message' => __('messages.table_full')], 422);
        }

        $table->players()->attach($user->id, ['joined_at' => now()]);
        $table->current_players = $table->players()->count();
        $table->status          = $table->current_players >= $table->max_players ? 'full' : 'playing';
        $table->save();

        event(new BlackjackTableUpdated($table));

        return response()->json(['redirect' => route('entertainment.blackjack.show', $table)]);
    }

    public function leaveTable(BlackjackTable $table)
    {
        $user = Auth::user();

        $table->players()->detach($user->id);
        $table->current_players = $table->players()->count();

        // Delete user-created tables when empty; keep preset tables
        if ($table->current_players <= 0 && !$table->is_preset) {
            event(new BlackjackTableUpdated($table->fill(['status' => 'closed'])));
            $table->delete();
            return redirect()->route('entertainment.blackjack');
        }

        $table->status = $table->current_players <= 0 ? 'waiting' : 'playing';
        $table->save();
        event(new BlackjackTableUpdated($table));

        return redirect()->route('entertainment.blackjack');
    }

    public function show(BlackjackTable $table)
    {
        $user = Auth::user();

        // Must be seated at the table
        if (!$table->players()->where('user_id', $user->id)->exists()) {
            return redirect()->route('entertainment.blackjack')
                ->with('error', __('messages.bj_not_at_table'));
        }

        $players = $table->players()->get();
        return view('entertainment.blackjack_room', compact('table', 'players'));
    }

    // ── Gameplay ──────────────────────────────────────────────────────────

    public function state(BlackjackTable $table)
    {
        $user  = Auth::user();
        $fresh = $user->fresh();
        $base  = [
            'z_coins' => $fresh->z_coins - $fresh->z_coins_frozen,
            'min_bet' => $table->min_bet,
            'max_bet' => $table->max_bet,
        ];

        $game = BlackjackGame::where('user_id', $user->id)
            ->where('blackjack_table_id', $table->id)
            ->latest()->first();

        if (!$game) {
            // Check if this player has already gone through the lobby
            $pivotPlayer = $table->players()->where('user_id', $user->id)->first();
            $isReady = $pivotPlayer && $pivotPlayer->pivot->is_ready;

            if (!$isReady) {
                // Show lobby waiting room
                return response()->json(array_merge($base, [
                    'phase'        => 'lobby',
                    'players'      => $this->getPlayersData($table),
                    'countdown_at' => Cache::get("bj_countdown_{$table->id}"),
                ]));
            }

            // Player ready but no game yet → straight to betting
            return response()->json(array_merge($base, ['phase' => 'betting']));
        }

        $state = BlackjackEngine::clientState($game, $user);
        return response()->json(array_merge($base, ['state' => $state]));
    }

    public function ready(BlackjackTable $table)
    {
        $user        = Auth::user();
        $pivotPlayer = $table->players()->where('user_id', $user->id)->first();

        if (!$pivotPlayer) {
            return response()->json(['error' => 'Not at table'], 403);
        }

        // Toggle ready
        $isReady = !$pivotPlayer->pivot->is_ready;
        $table->players()->updateExistingPivot($user->id, ['is_ready' => $isReady]);

        $players     = $this->getPlayersData($table);
        $allReady    = $players->every(fn($p) => $p['is_ready']);
        $countdownAt = null;

        if ($allReady && $players->count() >= 2) {
            $countdownAt = now()->addSeconds(5)->timestamp;
            Cache::put("bj_countdown_{$table->id}", $countdownAt, 30);
            event(new BlackjackRoomUpdated($table->id, 'countdown_start', $players->toArray(), $countdownAt));
        } else {
            Cache::forget("bj_countdown_{$table->id}");
            event(new BlackjackRoomUpdated($table->id, 'ready_update', $players->toArray()));
        }

        return response()->json([
            'players'      => $players,
            'countdown_at' => $countdownAt,
        ]);
    }

    private function getPlayersData(BlackjackTable $table): \Illuminate\Support\Collection
    {
        return $table->players()
            ->withPivot('is_ready')
            ->get()
            ->map(fn($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'is_ready' => (bool) $p->pivot->is_ready,
            ]);
    }

    public function deal(Request $request, BlackjackTable $table)
    {
        $request->validate(['bet' => 'required|integer|min:1']);
        $result = BlackjackEngine::deal($table, Auth::id(), (int) $request->bet);

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        $state = BlackjackEngine::clientState($result['game'], Auth::user());
        return response()->json(['state' => $state]);
    }

    public function action(Request $request, BlackjackTable $table)
    {
        $request->validate(['action' => 'required|in:hit,stand,double']);

        $user = Auth::user();
        $game = BlackjackGame::where('user_id', $user->id)
            ->where('blackjack_table_id', $table->id)
            ->latest()->first();

        if (!$game || $game->state['phase'] !== 'playing') {
            return response()->json(['error' => __('messages.bj_no_active_hand')], 422);
        }

        $game  = BlackjackEngine::processAction($game, $request->action);
        $state = BlackjackEngine::clientState($game, $user);
        return response()->json(['state' => $state]);
    }
}
