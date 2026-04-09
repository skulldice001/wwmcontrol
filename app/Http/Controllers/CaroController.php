<?php

namespace App\Http\Controllers;

use App\Events\CaroRoomUpdated;
use App\Events\CaroTableUpdated;
use App\Models\CaroGame;
use App\Models\CaroTable;
use App\Services\CaroEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaroController extends Controller
{
    // ── Lobby ─────────────────────────────────────────────────────────────────

    public function index()
    {
        $tables = CaroTable::with(['playerX:id,name', 'playerO:id,name'])
            ->whereIn('status', ['waiting', 'playing'])
            ->latest()
            ->get();

        return view('entertainment.caro', compact('tables'));
    }

    // ── Table management ──────────────────────────────────────────────────────

    public function createTable(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:60',
            'entry_fee' => 'required|integer|min:0|max:100000',
        ]);

        $user = Auth::user();

        // Check funds if entry fee required
        if ($data['entry_fee'] > 0 && $user->z_coins < $data['entry_fee']) {
            return response()->json(['error' => 'Không đủ Zoo để đặt cọc.'], 422);
        }

        // Leave any existing table first
        $this->leaveExistingTables($user->id);

        $table = CaroTable::create([
            'name'        => $data['name'],
            'entry_fee'   => (int) $data['entry_fee'],
            'player_x_id' => $user->id,
            'status'      => 'waiting',
        ]);

        event(new CaroTableUpdated($table));

        return response()->json(['redirect' => route('entertainment.caro.show', $table)]);
    }

    public function show(CaroTable $table)
    {
        $user = Auth::user();

        if ($table->player_x_id !== $user->id && $table->player_o_id !== $user->id) {
            return redirect()->route('entertainment.caro')->with('error', 'Bạn không ở bàn này.');
        }

        $mySymbol = ($table->player_x_id === $user->id) ? 'X' : 'O';
        $game     = $table->activeGame;

        return view('entertainment.caro_room', compact('table', 'mySymbol', 'game'));
    }

    public function join(CaroTable $table)
    {
        $user = Auth::user();

        if ($table->player_x_id === $user->id || $table->player_o_id === $user->id) {
            return response()->json(['redirect' => route('entertainment.caro.show', $table)]);
        }

        if ($table->status !== 'waiting') {
            return response()->json(['error' => 'Bàn này không mở.'], 422);
        }

        if ($table->player_o_id !== null) {
            return response()->json(['error' => 'Bàn đã đủ người.'], 422);
        }

        // Check funds
        if ($table->entry_fee > 0 && $user->z_coins < $table->entry_fee) {
            return response()->json(['error' => 'Không đủ Zoo để tham gia.'], 422);
        }

        $this->leaveExistingTables($user->id);

        $table->update(['player_o_id' => $user->id, 'status' => 'playing']);
        $table->load(['playerX', 'playerO']);

        // Auto-start game
        $game = CaroEngine::startGame($table);

        event(new CaroTableUpdated($table));
        event(new CaroRoomUpdated($table->id, 'game_started', [
            'state' => CaroEngine::clientState($game, $user->id),
        ]));

        return response()->json(['redirect' => route('entertainment.caro.show', $table)]);
    }

    public function leave(CaroTable $table)
    {
        $user = Auth::user();

        $isX = $table->player_x_id === $user->id;
        $isO = $table->player_o_id === $user->id;

        if (!$isX && !$isO) {
            return redirect()->route('entertainment.caro');
        }

        // Forfeit if game is in progress
        $game = $table->activeGame;
        if ($game) {
            $game = CaroEngine::forfeit($game, $user->id);
            $loser  = $isX ? $table->playerX : $table->playerO;
            $winner = $isX ? $table->playerO : $table->playerX;
            event(new CaroRoomUpdated($table->id, 'game_over', [
                'state'  => CaroEngine::clientState($game, $user->id),
                'reason' => 'forfeit',
                'winner_name' => $winner?->name,
            ]));
        }

        // Remove player from table
        if ($isX) {
            $table->update(['player_x_id' => null, 'status' => 'waiting']);
        } else {
            $table->update(['player_o_id' => null, 'status' => $table->player_x_id ? 'waiting' : 'finished']);
        }

        $table->refresh();
        if (!$table->player_x_id && !$table->player_o_id) {
            $table->delete();
        } else {
            event(new CaroTableUpdated($table));
            event(new CaroRoomUpdated($table->id, 'player_left', [
                'symbol' => $isX ? 'X' : 'O',
            ]));
        }

        return redirect()->route('entertainment.caro');
    }

    // ── Gameplay ──────────────────────────────────────────────────────────────

    public function move(Request $request, CaroTable $table)
    {
        $request->validate([
            'row' => 'required|integer|min:0',
            'col' => 'required|integer|min:0',
        ]);

        $user = Auth::user();
        $game = $table->activeGame;

        if (!$game) {
            return response()->json(['error' => 'Không có ván đang diễn ra.'], 422);
        }

        $result = CaroEngine::makeMove($game, $user->id, (int) $request->row, (int) $request->col);

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        $game  = $result['game'];
        $state = CaroEngine::clientState($game, $user->id);

        $type = ($game->status === 'finished') ? 'game_over' : 'move_made';
        event(new CaroRoomUpdated($table->id, $type, ['state' => $state]));

        return response()->json(['state' => $state]);
    }

    public function state(CaroTable $table)
    {
        $user = Auth::user();
        $game = $table->activeGame ?? $table->games()->latest()->first();

        if (!$game) {
            return response()->json([
                'status'       => 'waiting',
                'player_x'     => ['id' => $table->player_x_id, 'name' => $table->playerX?->name],
                'player_o'     => ['id' => $table->player_o_id, 'name' => $table->playerO?->name],
                'my_z_coins'   => $user->z_coins,
            ]);
        }

        return response()->json(array_merge(
            CaroEngine::clientState($game, $user->id),
            ['my_z_coins' => $user->z_coins]
        ));
    }

    public function requestTimeout(CaroTable $table)
    {
        $user = Auth::user();
        $game = $table->activeGame;

        if (!$game || $game->status !== 'playing') {
            return response()->json(['error' => 'Không có ván đang diễn ra.'], 422);
        }

        // Only the waiting player can request timeout
        $currentPlayerId = ($game->current_player === 'X') ? $table->player_x_id : $table->player_o_id;
        $waitingPlayerId  = ($game->current_player === 'X') ? $table->player_o_id : $table->player_x_id;

        if ($user->id !== $waitingPlayerId) {
            return response()->json(['error' => 'Không hợp lệ.'], 422);
        }

        // Check deadline has passed
        if ($game->turn_deadline && now()->lt($game->turn_deadline)) {
            return response()->json(['error' => 'Thời gian chưa hết.'], 422);
        }

        $game  = CaroEngine::forfeit($game, $currentPlayerId);
        $state = CaroEngine::clientState($game, $user->id);

        event(new CaroRoomUpdated($table->id, 'game_over', [
            'state'  => $state,
            'reason' => 'timeout',
        ]));

        return response()->json(['state' => $state]);
    }

    public function rematch(CaroTable $table)
    {
        $user = Auth::user();

        if ($table->player_x_id !== $user->id && $table->player_o_id !== $user->id) {
            return response()->json(['error' => 'Bạn không ở bàn này.'], 403);
        }

        if ($table->status !== 'finished') {
            return response()->json(['error' => 'Ván chưa kết thúc.'], 422);
        }

        if (!$table->player_x_id || !$table->player_o_id) {
            return response()->json(['error' => 'Thiếu người chơi.'], 422);
        }

        // Check funds for entry fee
        if ($table->entry_fee > 0) {
            $x = $table->playerX;
            $o = $table->playerO;
            if ($x->z_coins < $table->entry_fee || $o->z_coins < $table->entry_fee) {
                return response()->json(['error' => 'Không đủ Zoo để tái đấu.'], 422);
            }
        }

        // Swap X and O for fairness
        $table->update([
            'player_x_id' => $table->player_o_id,
            'player_o_id' => $table->player_x_id,
            'status'      => 'playing',
        ]);
        $table->load(['playerX', 'playerO']);

        $game  = CaroEngine::startGame($table);
        $state = CaroEngine::clientState($game, $user->id);

        event(new CaroTableUpdated($table));
        event(new CaroRoomUpdated($table->id, 'game_started', ['state' => $state]));

        return response()->json(['state' => $state]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function leaveExistingTables(int $userId): void
    {
        $existing = CaroTable::where(function ($q) use ($userId) {
            $q->where('player_x_id', $userId)->orWhere('player_o_id', $userId);
        })->whereIn('status', ['waiting', 'playing'])->get();

        foreach ($existing as $t) {
            $game = $t->activeGame;
            if ($game) {
                CaroEngine::forfeit($game, $userId);
            }
            if ($t->player_x_id === $userId) $t->player_x_id = null;
            if ($t->player_o_id === $userId) $t->player_o_id = null;
            if (!$t->player_x_id && !$t->player_o_id) {
                $t->delete();
            } else {
                $t->status = 'waiting';
                $t->save();
                event(new CaroTableUpdated($t));
            }
        }
    }
}
