<?php

namespace App\Http\Controllers;

use App\Events\PokerRoomUpdated;
use App\Models\PokerGame;
use App\Models\PokerTable;
use App\Services\Poker\Deck;
use App\Services\Poker\HandEvaluator;
use App\Services\Poker\GameEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PokerGameController extends Controller
{
    /** POST — start a new hand (called by EntertainmentController after all-ready) */
    public function start(PokerTable $table)
    {
        $humanIds = $table->players()->pluck('users.id')->toArray();
        $game     = GameEngine::startGame($table, $humanIds);

        $this->broadcast($table, $game);

        return response()->json(['state' => $this->clientState($game, Auth::id())]);
    }

    /** GET — current game state from this user's perspective */
    public function state(PokerTable $table)
    {
        $game = PokerGame::where('poker_table_id', $table->id)->latest()->first();
        if (!$game) {
            return response()->json(['state' => null]);
        }
        return response()->json(['state' => $this->clientState($game, Auth::id())]);
    }

    /** POST — human player action */
    public function action(Request $request, PokerTable $table)
    {
        $game = PokerGame::where('poker_table_id', $table->id)->latest()->first();
        if (!$game || $game->state['phase'] === 'showdown') {
            return response()->json(['error' => 'No active game'], 422);
        }

        $game = GameEngine::processAction(
            $game,
            $request->input('action'),
            (float) $request->input('amount', 0),
            Auth::id()
        );

        $this->broadcast($table, $game);

        return response()->json(['state' => $this->clientState($game, Auth::id())]);
    }

    // -------------------------------------------------------------------------

    /** Broadcast updated game state to all players in the room */
    private function broadcast(PokerTable $table, PokerGame $game): void
    {
        // Build state for each human seat so WebSocket can deliver the right view
        $s       = $game->state;
        $players = $table->players()->withPivot('is_ready')->get();
        $seats   = [];
        foreach ($players as $user) {
            $seats[$user->id] = $this->clientState($game, $user->id);
        }

        event(new PokerRoomUpdated($table->id, [
            'phase'  => $s['phase'],
            'seats'  => $seats,  // keyed by user_id
            'pot'    => (int) $s['pot'],
        ]));
    }

    /** Format game state for a specific user's browser */
    public function clientState(PokerGame $game, int $userId): array
    {
        $s     = $game->state;
        $phase = $s['phase'];

        // Find requesting user's seat index
        $myIdx = null;
        foreach ($s['players'] as $i => $p) {
            if (!$p['is_ai'] && $p['id'] === $userId) {
                $myIdx = $i;
                break;
            }
        }

        $players = [];
        foreach ($s['players'] as $i => $p) {
            // Show cards only for: own seat, or AI/others at showdown
            $revealCards = ($p['id'] === $userId) || ($phase === 'showdown' && $p['status'] !== 'folded');
            $cards = $revealCards
                ? array_map(fn($c) => ['suit' => $c['suit'], 'rank' => $c['rank'], 'img' => Deck::img($c)], $p['hole_cards'])
                : array_fill(0, count($p['hole_cards']), ['suit' => 'back', 'rank' => 'back', 'img' => null]);

            $handName = null;
            if ($phase === 'showdown' && $p['status'] !== 'folded') {
                $ev       = HandEvaluator::evaluate($p['hole_cards'], $s['community_cards']);
                $handName = $ev['name'];
            }

            $players[] = [
                'index'      => $i,
                'name'       => $p['name'],
                'chips'      => (int) $p['chips'],
                'bet'        => (int) $p['bet'],
                'total_bet'  => (int) $p['total_bet'],
                'status'     => $p['status'],
                'is_ai'      => $p['is_ai'],
                'is_dealer'  => $i === $s['dealer_index'],
                'is_sb'      => $i === $s['sb_index'],
                'is_bb'      => $i === $s['bb_index'],
                'is_current' => $i === $s['current_player'],
                'hole_cards' => $cards,
                'hand_name'  => $handName,
                'is_winner'  => in_array($i, $s['winner_info']['player_indices'] ?? []),
            ];
        }

        $myPlayer = $myIdx !== null ? $s['players'][$myIdx] : null;
        $toCall   = $myPlayer ? max(0, (int) $s['current_bet'] - (int) $myPlayer['bet']) : 0;
        $isMyTurn = $myIdx !== null
            && $s['current_player'] === $myIdx
            && $phase !== 'showdown'
            && ($myPlayer['status'] ?? '') === 'active'
            && ($myPlayer['pending'] ?? false);

        return [
            'game_id'         => $game->id,
            'phase'           => $phase,
            'pot'             => (int) $s['pot'],
            'current_bet'     => (int) $s['current_bet'],
            'big_blind'       => (int) $s['big_blind'],
            'community_cards' => array_map(fn($c) => [
                'suit' => $c['suit'], 'rank' => $c['rank'], 'img' => Deck::img($c),
            ], $s['community_cards']),
            'players'         => $players,
            'my_index'        => $myIdx,
            'is_my_turn'      => $isMyTurn,
            'to_call'         => $toCall,
            'can_check'       => $isMyTurn && $toCall === 0,
            'can_call'        => $isMyTurn && $toCall > 0,
            'min_raise'       => (int) ($s['current_bet'] + max($s['last_raise'], $s['big_blind'])),
            'my_chips'        => $myPlayer ? (int) $myPlayer['chips'] : 0,
            'winner_info'     => $s['winner_info'],
            'log'             => array_slice($s['log'] ?? [], -8),
        ];
    }
}
