<?php

namespace App\Http\Controllers;

use App\Events\BlackjackRoomUpdated;
use App\Events\BlackjackTableUpdated;
use App\Models\BlackjackRound;
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

        // Auto-join creator (no role yet)
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

        // max_players = player seats only (dealer is extra)
        $playerCount = $table->players()
            ->wherePivot('role', 'player')
            ->count();

        if ($playerCount >= $table->max_players) {
            return response()->json(['message' => __('messages.table_full')], 422);
        }

        $table->players()->attach($user->id, ['joined_at' => now(), 'role' => 'player']);
        $table->current_players = $table->players()->count();
        $table->status          = 'playing';
        $table->save();

        event(new BlackjackTableUpdated($table));
        event(new BlackjackRoomUpdated($table->id, 'ready_update', $this->getPlayersData($table)->toArray()));

        return response()->json(['redirect' => route('entertainment.blackjack.show', $table)]);
    }

    public function leaveTable(BlackjackTable $table)
    {
        $user = Auth::user();

        $table->players()->detach($user->id);
        $table->current_players = $table->players()->count();

        if ($table->current_players <= 0 && !$table->is_preset) {
            event(new BlackjackTableUpdated($table->fill(['status' => 'closed'])));
            $table->delete();
            return redirect()->route('entertainment.blackjack');
        }

        $table->status = $table->current_players <= 0 ? 'waiting' : 'playing';
        $table->save();
        event(new BlackjackTableUpdated($table));
        event(new BlackjackRoomUpdated($table->id, 'ready_update', $this->getPlayersData($table)->toArray()));

        return redirect()->route('entertainment.blackjack');
    }

    public function show(BlackjackTable $table)
    {
        $user = Auth::user();

        if (!$table->players()->where('user_id', $user->id)->exists()) {
            return redirect()->route('entertainment.blackjack')
                ->with('error', __('messages.bj_not_at_table'));
        }

        $pivot = $table->players()->where('user_id', $user->id)->first()->pivot;

        return view('entertainment.blackjack_room', [
            'table'   => $table,
            'myRole'  => $pivot->role  ?? 'player',
            'mySeat'  => $pivot->seat  ?? null,
        ]);
    }

    // ── Lobby: role/seat selection + ready ────────────────────────────────

    /**
     * POST {table}/role — choose role and seat before ready
     */
    public function chooseRole(Request $request, BlackjackTable $table)
    {
        $request->validate([
            'role' => 'required|in:player,dealer',
            'seat' => 'nullable|integer|min:1|max:7',
        ]);

        $user = Auth::user();
        $role = $request->role;
        $seat = $role === 'dealer' ? 0 : (int) $request->seat;

        if (!$table->players()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Not at table'], 403);
        }

        // Validate dealer slot availability
        if ($role === 'dealer') {
            $existingDealer = $table->players()
                ->wherePivot('role', 'dealer')
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($existingDealer) {
                return response()->json(['error' => __('messages.bj_dealer_taken')], 422);
            }
        }

        // Validate seat availability for player
        if ($role === 'player') {
            if (!$seat || $seat < 1 || $seat > 7) {
                return response()->json(['error' => 'Invalid seat number'], 422);
            }
            $seatTaken = $table->players()
                ->wherePivot('seat', $seat)
                ->where('user_id', '!=', $user->id)
                ->exists();
            if ($seatTaken) {
                return response()->json(['error' => __('messages.bj_seat_taken')], 422);
            }
        }

        $table->players()->updateExistingPivot($user->id, [
            'role'     => $role,
            'seat'     => $seat,
            'is_ready' => false, // reset ready on role change
        ]);

        $players = $this->getPlayersData($table);
        event(new BlackjackRoomUpdated($table->id, 'ready_update', $players->toArray()));

        return response()->json(['players' => $players]);
    }

    /**
     * POST {table}/ready — toggle ready
     */
    public function ready(BlackjackTable $table)
    {
        $user        = Auth::user();
        $pivotPlayer = $table->players()->where('user_id', $user->id)->first();

        if (!$pivotPlayer) {
            return response()->json(['error' => 'Not at table'], 403);
        }

        // Must have a role and seat chosen
        if (!$pivotPlayer->pivot->role || ($pivotPlayer->pivot->role === 'player' && !$pivotPlayer->pivot->seat)) {
            return response()->json(['error' => __('messages.bj_choose_role_first')], 422);
        }

        $isReady = !$pivotPlayer->pivot->is_ready;
        $table->players()->updateExistingPivot($user->id, ['is_ready' => $isReady]);

        $players     = $this->getPlayersData($table);
        $countdownAt = null;

        // Start condition: 1 ready dealer + ≥1 ready player
        $readyDealer  = $players->first(fn($p) => $p['role'] === 'dealer' && $p['is_ready']);
        $readyPlayers = $players->filter(fn($p) => $p['role'] === 'player' && $p['is_ready']);

        if ($readyDealer && $readyPlayers->count() >= 1) {
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

    // ── Gameplay ──────────────────────────────────────────────────────────

    /**
     * POST {table}/game/start — dealer starts the round
     */
    public function startRound(BlackjackTable $table)
    {
        $user  = Auth::user();
        $pivot = $table->players()->where('user_id', $user->id)->first()?->pivot;

        if (!$pivot || $pivot->role !== 'dealer') {
            return response()->json(['error' => 'Only the dealer can start the round'], 403);
        }

        // Validate start conditions
        $players     = $this->getPlayersData($table);
        $readyDealer = $players->first(fn($p) => $p['role'] === 'dealer' && $p['is_ready']);
        $readyCount  = $players->filter(fn($p) => $p['role'] === 'player' && $p['is_ready'])->count();

        if (!$readyDealer || $readyCount < 1) {
            return response()->json(['error' => __('messages.bj_not_enough_players')], 422);
        }

        // Clear any existing active round
        $table->rounds()->whereNotIn('phase', ['finished'])->delete();
        Cache::forget("bj_countdown_{$table->id}");

        // Reset all ready flags
        $table->players()->each(function ($p) use ($table) {
            $table->players()->updateExistingPivot($p->id, ['is_ready' => false]);
        });

        $round = BlackjackEngine::startRound($table);

        $clientState = BlackjackEngine::clientState($round, $user);
        event(new BlackjackRoomUpdated($table->id, 'round_started', [], null, $clientState));

        return response()->json(['round' => $clientState]);
    }

    /**
     * GET {table}/game/state — returns current state
     */
    public function state(BlackjackTable $table)
    {
        $user  = Auth::user();
        $fresh = $user->fresh();

        $pivot = $table->players()->where('user_id', $user->id)->first()?->pivot;

        $base = [
            'z_coins' => $fresh->z_coins - $fresh->z_coins_frozen,
            'min_bet' => $table->min_bet,
            'max_bet' => $table->max_bet,
            'my_role' => $pivot?->role,
            'my_seat' => $pivot?->seat,
        ];

        $round = $table->activeRound();

        if (!$round) {
            return response()->json(array_merge($base, [
                'phase'        => 'lobby',
                'players'      => $this->getPlayersData($table),
                'countdown_at' => Cache::get("bj_countdown_{$table->id}"),
            ]));
        }

        $clientState = BlackjackEngine::clientState($round, $user);
        return response()->json(array_merge($base, ['round' => $clientState]));
    }

    /**
     * POST {table}/game/deal — player places bet
     */
    public function deal(Request $request, BlackjackTable $table)
    {
        $request->validate(['bet' => 'required|integer|min:1']);

        $round = $table->activeRound();
        if (!$round || $round->phase !== 'betting') {
            return response()->json(['error' => 'Not in betting phase'], 422);
        }

        $result = BlackjackEngine::placeBet($round, Auth::id(), (int) $request->bet);

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        $round       = $result['round'];
        $clientState = BlackjackEngine::clientState($round, Auth::user());
        event(new BlackjackRoomUpdated(
            $table->id,
            $round->phase === 'player_turns' ? 'cards_dealt' : 'bet_placed',
            [], null, $clientState
        ));

        return response()->json(['round' => $clientState]);
    }

    /**
     * POST {table}/game/action — hit | stand | double
     */
    public function action(Request $request, BlackjackTable $table)
    {
        $request->validate(['action' => 'required|in:hit,stand,double']);

        $round = $table->activeRound();
        if (!$round) {
            return response()->json(['error' => 'No active round'], 422);
        }

        $result = BlackjackEngine::processAction($round, Auth::id(), $request->action);

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        $round       = $result['round'];
        $clientState = BlackjackEngine::clientState($round, Auth::user());

        $eventType = match ($round->phase) {
            'dealer_turn' => 'dealer_turn',
            'finished'    => 'round_finished',
            default       => 'player_acted',
        };

        event(new BlackjackRoomUpdated($table->id, $eventType, [], null, $clientState));

        return response()->json(['round' => $clientState]);
    }

    /**
     * POST {table}/game/dealer-action — dealer hits or stands manually
     */
    public function dealerAction(Request $request, BlackjackTable $table)
    {
        $request->validate(['action' => 'required|in:hit,stand']);

        $round = $table->activeRound();
        if (!$round) {
            return response()->json(['error' => 'No active round'], 422);
        }

        $result = BlackjackEngine::dealerAction($round, Auth::id(), $request->action);

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        $round       = $result['round'];
        $clientState = BlackjackEngine::clientState($round, Auth::user());

        $eventType = $round->phase === 'finished' ? 'round_finished' : 'dealer_turn';
        event(new BlackjackRoomUpdated($table->id, $eventType, [], null, $clientState));

        return response()->json(['round' => $clientState]);
    }

    /**
     * POST {table}/game/next — dealer starts next round
     */
    public function nextRound(BlackjackTable $table)
    {
        $user  = Auth::user();
        $pivot = $table->players()->where('user_id', $user->id)->first()?->pivot;

        if (!$pivot || $pivot->role !== 'dealer') {
            return response()->json(['error' => 'Only the dealer can start next round'], 403);
        }

        $round = $table->activeRound();
        if (!$round || $round->phase !== 'finished') {
            return response()->json(['error' => 'Round not finished yet'], 422);
        }

        $round = BlackjackEngine::startRound($table);

        $clientState = BlackjackEngine::clientState($round, $user);
        event(new BlackjackRoomUpdated($table->id, 'round_started', [], null, $clientState));

        return response()->json(['round' => $clientState]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function getPlayersData(BlackjackTable $table): \Illuminate\Support\Collection
    {
        return $table->players()
            ->withPivot('is_ready', 'role', 'seat')
            ->get()
            ->map(fn($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'avatar'   => $p->discord_avatar ?? null,
                'is_ready' => (bool) $p->pivot->is_ready,
                'role'     => $p->pivot->role ?? 'player',
                'seat'     => $p->pivot->seat,
            ]);
    }
}
