<?php

namespace App\Http\Controllers;

use App\Events\BingoRoomUpdated;
use App\Jobs\BingoCallJob;
use App\Models\BingoGame;
use App\Models\BingoTable;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BingoController extends Controller
{
    // ── Views ─────────────────────────────────────────────────────────────────

    /**
     * Lobby: list all Bingo tables.
     */
    public function index()
    {
        $tables = BingoTable::all();
        $userId = Auth::id();

        return view('entertainment.bingo', compact('tables', 'userId'));
    }

    /**
     * Room view for a specific table.
     */
    public function show(BingoTable $table)
    {
        $userId   = Auth::id();
        $userName = Auth::user()->name;

        return view('entertainment.bingo_room', compact('table', 'userId', 'userName'));
    }

    // ── Table management ──────────────────────────────────────────────────────

    /**
     * Create a new Bingo table and auto-join the creator.
     */
    public function createTable(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:60',
            'entry_fee'  => 'required|integer|in:10,50,100,500',
        ]);

        $data['status']          = 'waiting';
        $data['current_players'] = 0;
        $data['max_players']     = 10;
        $data['min_players']     = 2;

        $table = BingoTable::create($data);

        // Auto-join creator
        $user = Auth::user();
        $table->players()->attach($user->id, [
            'joined_at' => now(),
            'is_ready'  => false,
        ]);
        $table->current_players = 1;
        $table->save();

        return redirect()->route('entertainment.bingo.show', $table);
    }

    /**
     * Join an existing table.
     */
    public function joinTable(BingoTable $table)
    {
        $user = Auth::user();

        // Already seated — redirect straight to room
        if ($table->players()->where('user_id', $user->id)->exists()) {
            return redirect()->route('entertainment.bingo.show', $table);
        }

        // Check capacity
        if ($table->current_players >= $table->max_players) {
            return back()->with('error', 'Bàn đã đầy.');
        }

        $table->players()->attach($user->id, [
            'joined_at' => now(),
            'is_ready'  => false,
        ]);
        $table->increment('current_players');

        event(new BingoRoomUpdated($table->id, [
            'type'    => 'player_joined',
            'players' => $this->playerData($table->fresh()->players()->get()),
        ]));

        return redirect()->route('entertainment.bingo.show', $table);
    }

    /**
     * Leave the table. Delete table when empty.
     */
    public function leaveTable(BingoTable $table)
    {
        $user = Auth::user();

        $table->players()->detach($user->id);
        $table->decrement('current_players');
        $table->refresh();

        if ($table->current_players <= 0) {
            $table->delete();
        }

        return redirect()->route('entertainment.bingo');
    }

    // ── API endpoints ─────────────────────────────────────────────────────────

    /**
     * Return current room state (players + active game state) for polling / initial load.
     */
    public function state(BingoTable $table)
    {
        $players = $table->players()->get();
        $game    = $table->games()->latest()->first();

        $response = [
            'players' => $this->playerData($players),
        ];

        if ($game && $game->state['phase'] === 'playing') {
            $response['game'] = $this->clientState($game, Auth::id());
        } elseif ($game && $game->state['phase'] === 'finished') {
            $response['game'] = $this->clientState($game, Auth::id());
        }

        return response()->json($response);
    }

    /**
     * Toggle ready status for the authenticated user.
     * Starts the game when all players are ready and min_players is met.
     */
    public function ready(BingoTable $table)
    {
        $userId = Auth::id();

        // Toggle is_ready for this user
        $pivot = DB::table('bingo_table_players')
            ->where('bingo_table_id', $table->id)
            ->where('user_id', $userId)
            ->first();

        if (!$pivot) {
            return response()->json(['error' => 'Bạn chưa ngồi vào bàn này.'], 422);
        }

        $newReady = !$pivot->is_ready;
        DB::table('bingo_table_players')
            ->where('bingo_table_id', $table->id)
            ->where('user_id', $userId)
            ->update(['is_ready' => $newReady]);

        // Re-fetch fresh pivot data after update
        $players      = $table->fresh()->players()->get();
        $totalPlayers = $players->count();
        $readyCount   = $players->filter(fn($p) => (bool) $p->pivot->is_ready)->count();
        $allReady     = $totalPlayers >= $table->min_players && $readyCount === $totalPlayers;

        if (!$allReady) {
            event(new BingoRoomUpdated($table->id, [
                'type'    => 'ready_update',
                'players' => $this->playerData($players),
            ]));

            return response()->json([
                'game_started' => false,
                'players'      => $this->playerData($players),
            ]);
        }

        // ── Start the game ────────────────────────────────────────────────────
        $humanIds = $players->pluck('id')->toArray();

        // Deduct entry fees inside a transaction
        try {
            $this->deductEntryFees($humanIds, $table->entry_fee);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // Build per-player state entries
        $playerStates = $players->map(function ($player) {
            return [
                'id'        => $player->id,
                'name'      => $player->name,
                'card'      => $this->generateCard(),
                'is_winner' => false,
            ];
        })->values()->all();

        // Shuffle numbers 1-75
        $allNumbers = range(1, 75);
        shuffle($allNumbers);

        $startedAt = time();

        $initialState = [
            'phase'          => 'playing',
            'players'        => $playerStates,
            'all_numbers'    => array_values($allNumbers),
            'called_numbers' => [],
            'current_index'  => 0,
            'winner_ids'     => [],
            'winner_line'    => null,
            'entry_fee'      => $table->entry_fee,
            'pot'            => count($humanIds) * $table->entry_fee,
            'call_started_at'=> $startedAt,
        ];

        $game = BingoGame::create([
            'bingo_table_id' => $table->id,
            'state'          => $initialState,
        ]);

        // Dispatch first call after 5 seconds
        BingoCallJob::dispatch($table->id, $game->id, 0, $startedAt)
            ->delay(now()->addSeconds(5));

        $seats = $this->buildSeats($players, $game);

        event(new BingoRoomUpdated($table->id, [
            'type'  => 'game_started',
            'seats' => $seats,
        ]));

        return response()->json([
            'game_started' => true,
            'state'        => $this->clientState($game, $userId),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Generate a standard 5×5 BINGO card.
     *
     * Columns:
     *   B (col 0): 5 unique numbers from 1–15
     *   I (col 1): 5 unique numbers from 16–30
     *   N (col 2): 5 unique numbers from 31–45; center cell (row 2) = 0 (FREE)
     *   G (col 3): 5 unique numbers from 46–60
     *   O (col 4): 5 unique numbers from 61–75
     *
     * Returns grid[row][col].
     */
    private function generateCard(): array
    {
        $ranges = [
            [1,  15],
            [16, 30],
            [31, 45],
            [46, 60],
            [61, 75],
        ];

        $grid = array_fill(0, 5, array_fill(0, 5, 0));

        for ($col = 0; $col < 5; $col++) {
            [$min, $max] = $ranges[$col];
            $pool = range($min, $max);
            shuffle($pool);
            $picked = array_slice($pool, 0, 5);

            for ($row = 0; $row < 5; $row++) {
                $grid[$row][$col] = $picked[$row];
            }
        }

        // Center cell is FREE space
        $grid[2][2] = 0;

        return $grid;
    }

    /**
     * Format game state for a specific player's browser.
     * Includes only their own card; other players show name/status only.
     */
    private function clientState(BingoGame $game, int $userId): array
    {
        $state       = $game->state;
        $calledNums  = $state['called_numbers'] ?? [];
        $calledSet   = array_flip($calledNums);

        $myCard = null;

        $playerList = array_map(function ($player) use ($userId, $calledSet, $calledNums, &$myCard) {
            $markCount = $this->countMarked($player['card'], $calledNums);

            if ($player['id'] === $userId) {
                $myCard = $player['card'];
            }

            return [
                'id'         => $player['id'],
                'name'       => $player['name'],
                'is_winner'  => $player['is_winner'],
                'mark_count' => $markCount,
            ];
        }, $state['players'] ?? []);

        return [
            'phase'          => $state['phase'],
            'my_card'        => $myCard,
            'called_numbers' => $calledNums,
            'current_index'  => $state['current_index'] ?? 0,
            'players'        => $playerList,
            'pot'            => $state['pot'] ?? 0,
            'entry_fee'      => $state['entry_fee'] ?? 0,
            'winner_ids'     => $state['winner_ids'] ?? [],
        ];
    }

    /**
     * Count how many cells on a card are marked (called or FREE).
     */
    private function countMarked(array $card, array $calledNums): int
    {
        $calledSet = array_flip($calledNums);
        $count     = 0;

        for ($r = 0; $r < 5; $r++) {
            for ($c = 0; $c < 5; $c++) {
                $n = $card[$r][$c];
                if ($n === 0 || isset($calledSet[$n])) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Map a player collection to a simple [{id, name, is_ready}] array.
     */
    private function playerData($players): array
    {
        return $players->map(fn($p) => [
            'id'       => $p->id,
            'name'     => $p->name,
            'is_ready' => (bool) $p->pivot->is_ready,
        ])->values()->all();
    }

    /**
     * Build a seats map keyed by user_id with card and is_winner info.
     */
    private function buildSeats($players, BingoGame $game): array
    {
        $state  = $game->state;
        $byId   = collect($state['players'])->keyBy('id');
        $seats  = [];

        foreach ($players as $player) {
            $entry = $byId->get($player->id);
            $seats[$player->id] = [
                'id'        => $player->id,
                'name'      => $player->name,
                'is_winner' => $entry['is_winner'] ?? false,
            ];
        }

        return $seats;
    }

    /**
     * Deduct entry_fee from each player in a DB transaction.
     * Uses lockForUpdate to prevent race conditions.
     *
     * @throws \Exception if any player has insufficient balance
     */
    private function deductEntryFees(array $userIds, int $entryFee): void
    {
        DB::transaction(function () use ($userIds, $entryFee) {
            foreach ($userIds as $uid) {
                $user = User::lockForUpdate()->find($uid);

                if (!$user) {
                    throw new \Exception("Không tìm thấy người chơi #{$uid}.");
                }

                $available = $user->z_coins - ($user->z_coins_frozen ?? 0);

                if ($available < $entryFee) {
                    throw new \Exception("Người chơi {$user->name} không đủ Z-Coins.");
                }

                $balBefore = $user->z_coins;
                $user->decrement('z_coins', $entryFee);

                ZooCoinTransaction::create([
                    'user_id'        => $uid,
                    'type'           => 'bingo_bet',
                    'amount'         => $entryFee,
                    'balance_before' => $balBefore,
                    'balance_after'  => $balBefore - $entryFee,
                    'note'           => 'Bingo mua vé tham gia',
                ]);
            }
        });
    }
}
