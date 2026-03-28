<?php

namespace App\Http\Controllers;

use App\Events\PokerRoomUpdated;
use App\Events\PokerTableUpdated;
use App\Models\BlackjackTable;
use App\Models\TaixiuTable;
use Illuminate\Support\Facades\DB;
use App\Models\PokerGame;
use App\Models\PokerTable;
use App\Models\User;
use App\Services\Poker\GameEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntertainmentController extends Controller
{
    public function index()
    {
        $stats = [
            'poker'    => $this->tableStats(PokerTable::all()),
            'blackjack'=> $this->tableStats(BlackjackTable::all()),
            'taixiu'   => $this->tableStats(TaixiuTable::all()),
        ];
        return view('entertainment.index', compact('stats'));
    }

    private function tableStats($tables): array
    {
        return [
            'total'   => $tables->count(),
            'playing' => $tables->where('status', 'playing')->count(),
            'waiting' => $tables->where('status', 'waiting')->count(),
        ];
    }

    public function poker()
    {
        try {
            $tables = PokerTable::all();
        } catch (\Exception $e) {
            $tables = collect([
                new PokerTable(['id' => 1, 'name' => 'Demo Table 1', 'blinds' => '1/2', 'buy_in_min' => 100, 'buy_in_max' => 200, 'max_players' => 9, 'current_players' => 5, 'status' => 'playing']),
                new PokerTable(['id' => 2, 'name' => 'Demo Table 2', 'blinds' => '2/5', 'buy_in_min' => 200, 'buy_in_max' => 500, 'max_players' => 6, 'current_players' => 0, 'status' => 'waiting']),
            ]);
        }
        return view('entertainment.poker', compact('tables'));
    }

    public function blackjack()
    {
        $tables = BlackjackTable::all();

        if (request()->expectsJson()) {
            return response()->json(['tables' => $tables->values()]);
        }

        return view('entertainment.blackjack', compact('tables'));
    }

    public function showTable(PokerTable $table)
    {
        return view('entertainment.poker_room', compact('table'));
    }

    public function createTable(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:60',
            'type'        => 'required|in:no_limit_holdem,pot_limit_omaha',
            'small_blind' => 'required|numeric|min:1',
            'big_blind'   => 'required|numeric|min:2',
            'min_buy_in'  => 'required|numeric|min:1',
            'max_players' => 'required|integer|min:2|max:9',
        ]);

        // Entry fee per hand = big blind; starting chip stack = 100 big blinds
        $data['max_buy_in']      = $data['big_blind'] * 100;
        $data['status']          = 'waiting';
        $data['current_players'] = 0;

        $table = PokerTable::create($data);

        // Auto-join creator
        $user = Auth::user();
        $table->players()->attach($user->id, ['joined_at' => now()]);
        $table->current_players = 1;
        $table->status = 'playing';
        $table->save();

        event(new PokerTableUpdated($table));

        return response()->json(['redirect' => route('entertainment.poker.show', $table)]);
    }

    public function joinTable(PokerTable $table)
    {
        $user = Auth::user();

        // Already sitting at this table - idempotent, just redirect in
        if ($table->players()->where('user_id', $user->id)->exists()) {
            return response()->json(['redirect' => route('entertainment.poker.show', $table)]);
        }

        // If user is at a different table, remove them first
        $otherTable = PokerTable::whereHas('players', fn($q) => $q->where('user_id', $user->id))
            ->where('id', '!=', $table->id)
            ->first();

        if ($otherTable) {
            $otherTable->players()->detach($user->id);
            $otherTable->current_players = $otherTable->players()->count();
            $otherTable->status = $otherTable->current_players <= 0 ? 'waiting' : 'playing';
            $otherTable->save();
            event(new PokerTableUpdated($otherTable));
        }

        // Check capacity after potential vacating
        if ($table->current_players >= $table->max_players) {
            return response()->json(['message' => __('messages.table_full')], 422);
        }

        // Seat the player — catch race condition where two requests slip past the exists() check
        try {
            $table->players()->attach($user->id, ['joined_at' => now()]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json(['redirect' => route('entertainment.poker.show', $table)]);
        }
        $table->current_players = $table->players()->count();
        $table->status = $table->current_players >= $table->max_players ? 'full' : 'playing';
        $table->save();

        event(new PokerTableUpdated($table));

        return response()->json(['redirect' => route('entertainment.poker.show', $table)]);
    }

    public function leaveTable(PokerTable $table)
    {
        $user = Auth::user();

        // Handle active game: force-fold the leaving player
        $game = PokerGame::where('poker_table_id', $table->id)->latest()->first();
        if ($game && $game->state['phase'] !== 'showdown') {
            $humanIds  = $table->players()->pluck('users.id')->toArray();
            $updatedGame = GameEngine::forceLeave($game, $user->id);

            if ($updatedGame) {
                // Settle Z-coins if the force-fold triggered showdown
                if ($updatedGame->state['phase'] === 'showdown') {
                    $bigBlind   = (int) ($updatedGame->state['big_blind'] ?? 1);
                    $startStack = $bigBlind * 100;
                    foreach ($updatedGame->state['players'] as $p) {
                        if ($p['is_ai'] || !in_array($p['id'], $humanIds)) continue;
                        $finalChips = (int) $p['chips'];
                        $payout     = (int) floor($finalChips * $bigBlind / $startStack);
                        if ($payout > 0) {
                            User::where('id', $p['id'])->increment('z_coins', $payout);
                        }
                    }
                    // Reset ready flags so remaining players can start next hand
                    DB::table('poker_table_players')
                        ->where('poker_table_id', $table->id)
                        ->update(['is_ready' => false]);
                }

                // Tell remaining clients to refresh their state
                event(new PokerRoomUpdated($table->id, ['type' => 'refresh']));
            }
        }

        $table->players()->detach($user->id);
        $table->current_players = $table->players()->count();

        if ($table->current_players <= 0) {
            event(new PokerTableUpdated($table->fill(['status' => 'closed'])));
            $table->delete();

            return redirect()->route('entertainment.poker')
                ->with('success', __('messages.table_closed'));
        }

        $table->status = 'playing';
        $table->save();

        event(new PokerTableUpdated($table));

        return redirect()->route('entertainment.poker');
    }

    public function testUpdate()
    {
        try {
            $table = PokerTable::inRandomOrder()->first();
            if ($table) {
                $table->current_players = rand(0, $table->max_players);
                if ($table->current_players == $table->max_players) {
                    $table->status = 'full';
                } elseif ($table->current_players > 0) {
                    $table->status = 'playing';
                } else {
                    $table->status = 'waiting';
                }
                $table->save();
            } else {
                throw new \Exception("No tables found");
            }
        } catch (\Exception $e) {
            $table = new PokerTable([
                'id' => rand(1, 2),
                'name' => 'Demo Table ' . rand(1, 2),
                'blinds' => '1/2',
                'buy_in_min' => 100,
                'buy_in_max' => 200,
                'max_players' => 9,
                'current_players' => rand(0, 9),
                'status' => 'playing'
            ]);
            
            if ($table->current_players == $table->max_players) {
                $table->status = 'full';
            } elseif ($table->current_players > 0) {
                $table->status = 'playing';
            } else {
                $table->status = 'waiting';
            }
        }

        event(new PokerTableUpdated($table));
        
        return response()->json(['success' => true, 'table' => $table]);
    }
}
