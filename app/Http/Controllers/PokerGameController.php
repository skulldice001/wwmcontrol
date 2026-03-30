<?php

namespace App\Http\Controllers;

use App\Events\PokerRoomUpdated;
use App\Jobs\AutoFoldJob;
use App\Models\PokerGame;
use App\Models\PokerMessage;
use App\Models\PokerTable;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use App\Services\Poker\HandEvaluator;
use App\Services\Poker\GameEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PokerGameController extends Controller
{
    /** POST — start a new hand (called when player clicks "New Hand") */
    public function start(PokerTable $table)
    {
        $humanIds = $table->players()->pluck('users.id')->toArray();
        $minRequired = $table->is_ai_mode ? 1 : 2;

        if (count($humanIds) < $minRequired) {
            return response()->json(['error' => 'Cần ít nhất ' . $minRequired . ' người chơi để bắt đầu ván mới.'], 422);
        }

        if (!$this->deductBuyIn($table, $humanIds, $table->is_ai_mode)) {
            return response()->json([
                'error' => 'Không đủ Z-Coin để vào ván (cần ít nhất ' . number_format($table->min_buy_in) . ' Z)',
            ], 422);
        }

        $game = GameEngine::startGame($table, $humanIds);
        if ($table->is_ai_mode) {
            $game = GameEngine::runAI($game);
        }
        $this->dispatchTurnTimer($table, $game);
        $this->broadcast($table, $game);

        return response()->json(['state' => $this->clientState($game, Auth::id())]);
    }

    /** GET — current game state + lobby player list */
    public function state(PokerTable $table)
    {
        $players = $table->players()->get();
        $game    = PokerGame::where('poker_table_id', $table->id)->latest()->first();
        if (!$game || $game->state['phase'] === 'showdown') {
            return response()->json(['state' => null, 'players' => $this->playerData($players)]);
        }
        return response()->json([
            'state'   => $this->clientState($game, Auth::id()),
            'players' => $this->playerData($players),
        ]);
    }

    /** POST — toggle ready flag; starts game when all players are ready */
    public function ready(PokerTable $table)
    {
        $user   = Auth::user();
        $seated = $table->players()->where('user_id', $user->id)->first();
        if (!$seated) {
            return response()->json(['error' => 'Not seated at this table'], 422);
        }

        $newReady = !(bool) $seated->pivot->is_ready;
        $table->players()->updateExistingPivot($user->id, ['is_ready' => $newReady]);

        $players  = $table->players()->get();
        $minReady = $table->is_ai_mode ? 1 : 2;
        $allReady = $players->count() >= $minReady && $players->every(fn($p) => $p->pivot->is_ready);

        if ($allReady) {
            $humanIds = $players->pluck('id')->toArray();

            if (!$this->deductBuyIn($table, $humanIds, $table->is_ai_mode)) {
                // Reset all ready flags so the lobby is not stuck
                DB::table('poker_table_players')
                    ->where('poker_table_id', $table->id)
                    ->update(['is_ready' => false]);

                $freshPlayers = $table->players()->get();
                $playersData  = $this->playerData($freshPlayers);
                event(new PokerRoomUpdated($table->id, ['type' => 'ready_update', 'players' => $playersData]));

                return response()->json([
                    'game_started' => false,
                    'error'        => 'Một hoặc nhiều người chơi không đủ Z-Coin (cần ít nhất ' . number_format($table->min_buy_in) . ' Z)',
                    'players'      => $playersData,
                ]);
            }

            $game  = GameEngine::startGame($table, $humanIds);
            if ($table->is_ai_mode) {
                $game = GameEngine::runAI($game);
            }
            $this->dispatchTurnTimer($table, $game);
            $seats = $this->buildSeats($players, $game);

            event(new PokerRoomUpdated($table->id, [
                'type'  => 'game_started',
                'seats' => $seats,
                'phase' => $game->state['phase'],
                'pot'   => (int) $game->state['pot'],
            ]));

            return response()->json([
                'game_started' => true,
                'state'        => $seats[$user->id],
            ]);
        }

        $playersData = $this->playerData($players);
        event(new PokerRoomUpdated($table->id, [
            'type'    => 'ready_update',
            'players' => $playersData,
        ]));

        return response()->json([
            'game_started' => false,
            'is_ready'     => $newReady,
            'players'      => $playersData,
        ]);
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

        // Let AI act after human action (may chain through multiple AI turns)
        if ($table->is_ai_mode && $game->state['phase'] !== 'showdown') {
            $game = GameEngine::runAI($game);
        }

        // Pay out Z-Coins when the hand reaches showdown
        if ($game->state['phase'] === 'showdown') {
            $humanIds = $table->players()->pluck('users.id')->toArray();
            $this->settleZCoins($game, $humanIds, $table->is_ai_mode);
            // Reset ready flags — players must re-ready for next hand
            DB::table('poker_table_players')
                ->where('poker_table_id', $table->id)
                ->update(['is_ready' => false]);
        } else {
            $this->dispatchTurnTimer($table, $game);
        }

        $this->broadcast($table, $game);

        return response()->json(['state' => $this->clientState($game, Auth::id())]);
    }

    // -------------------------------------------------------------------------

    /** Broadcast updated game state to all players in the room */
    private function broadcast(PokerTable $table, PokerGame $game): void
    {
        $s     = $game->state;
        $seats = $this->buildSeats($table->players()->get(), $game);

        event(new PokerRoomUpdated($table->id, [
            'type'  => 'game_update',
            'phase' => $s['phase'],
            'seats' => $seats,
            'pot'   => (int) $s['pot'],
        ]));
    }

    /** Map a players collection to the lobby data array */
    private function playerData($players): array
    {
        return $players->map(fn($p) => [
            'id'       => $p->id,
            'name'     => $p->name,
            'is_ready' => (bool) $p->pivot->is_ready,
        ])->values()->toArray();
    }

    /** Build per-user game state array keyed by user_id */
    private function buildSeats($players, PokerGame $game): array
    {
        $seats = [];
        foreach ($players as $p) {
            $seats[$p->id] = $this->clientState($game, $p->id);
        }
        return $seats;
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
                ? array_map(fn($c) => ['suit' => $c['suit'], 'rank' => $c['rank']], $p['hole_cards'])
                : array_fill(0, count($p['hole_cards']), ['suit' => 'back', 'rank' => 'back']);

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
            'community_cards' => array_map(fn($c) => ['suit' => $c['suit'], 'rank' => $c['rank']], $s['community_cards']),
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
            'z_coins'         => User::find($userId)?->z_coins ?? 0,
            'turn_started_at' => $s['turn_started_at'] ?? null,
            'is_ai_mode'      => (bool) ($s['is_ai_mode'] ?? false),
        ];
    }

    // -------------------------------------------------------------------------

    /** Dispatch a 60-second auto-fold job if the current player is human. */
    private function dispatchTurnTimer(PokerTable $table, PokerGame $game): void
    {
        $state = $game->state;
        if ($state['phase'] === 'showdown') return;

        $p = $state['players'][$state['current_player']];
        if ($p['is_ai'] || $p['status'] !== 'active' || !$p['pending']) return;

        AutoFoldJob::dispatch(
            $table->id,
            $game->id,
            $state['current_player'],
            $state['turn_started_at']
        )->delay(now()->addSeconds(60));
    }

    /** Deduct buy-in from all human players atomically. Returns false if any lack funds. */
    private function deductBuyIn(PokerTable $table, array $humanIds, bool $isAiMode = false): bool
    {
        if (empty($humanIds)) return true;
        $minBalance = (int) $table->min_buy_in;
        $entryFee   = (int) $table->big_blind;

        return DB::transaction(function () use ($humanIds, $minBalance, $entryFee, $isAiMode) {
            $users = User::whereIn('id', $humanIds)->lockForUpdate()->get();
            foreach ($users as $user) {
                $available = $user->z_coins - $user->z_coins_frozen;
                if ($available < $minBalance) return false;
            }
            foreach ($users as $user) {
                $balBefore = $user->z_coins;
                $user->decrement('z_coins', $entryFee);
                ZooCoinTransaction::create([
                    'user_id'        => $user->id,
                    'type'           => $isAiMode ? 'poker_ai_bet' : 'poker_bet',
                    'amount'         => $entryFee,
                    'balance_before' => $balBefore,
                    'balance_after'  => $balBefore - $entryFee,
                    'note'           => $isAiMode ? 'Poker vs AI buy-in' : 'Poker buy-in',
                ]);
            }
            return true;
        });
    }

    // ── Chat ──────────────────────────────────────────────────────────────

    /** GET {table}/chat — fetch last 100 messages */
    public function messages(PokerTable $table)
    {
        $messages = PokerMessage::where('table_id', $table->id)
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

    /** POST {table}/chat — send a chat message */
    public function sendMessage(Request $request, PokerTable $table)
    {
        $request->validate(['message' => 'required|string|max:500']);

        $user = Auth::user();

        if (!$table->players()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Not at table'], 403);
        }

        $msg = PokerMessage::create([
            'table_id' => $table->id,
            'user_id'  => $user->id,
            'message'  => $request->message,
        ]);

        $payload = [
            'type'    => 'chat_message',
            'chat_message' => [
                'id'      => $msg->id,
                'user_id' => $user->id,
                'name'    => $user->name,
                'avatar'  => $user->discord_avatar ?? null,
                'message' => $msg->message,
                'time'    => $msg->created_at->format('H:i'),
            ],
        ];

        event(new PokerRoomUpdated($table->id, $payload));

        return response()->json(['message' => $payload['chat_message']]);
    }

    /** Credit each human player their proportional z-coin payout at showdown.
     *  Chips are scaled 100× entry fee (big_blind), so we convert back:
     *  payout_z = floor(final_chips * big_blind / (big_blind * 100)) = floor(final_chips / 100)
     *  AI mode: payout is divided by 10; total daily AI winnings capped at 5 000 Zoo.
     */
    private function settleZCoins(PokerGame $game, array $humanIds, bool $isAiMode = false): void
    {
        $bigBlind   = (int) ($game->state['big_blind'] ?? 1);
        $startStack = $bigBlind * 100;

        foreach ($game->state['players'] as $p) {
            if ($p['is_ai'] || !in_array($p['id'], $humanIds)) continue;

            $finalChips = (int) $p['chips'];
            $payout     = (int) floor($finalChips * $bigBlind / $startStack);

            if ($isAiMode) {
                $payout = (int) floor($payout / 10);
                $payout = $this->capAiPayout($p['id'], $payout);
            }

            if ($payout > 0) {
                $user      = User::find($p['id']);
                $balBefore = $user->z_coins;
                $user->increment('z_coins', $payout);
                ZooCoinTransaction::create([
                    'user_id'        => $user->id,
                    'type'           => $isAiMode ? 'poker_ai_payout' : 'poker_payout',
                    'amount'         => $payout,
                    'balance_before' => $balBefore,
                    'balance_after'  => $balBefore + $payout,
                    'note'           => $isAiMode ? 'Poker vs AI payout' : 'Poker payout',
                ]);
            }
        }
    }

    /** Returns max additional AI payout allowed today (daily cap = 5 000 Zoo net). */
    private function capAiPayout(int $userId, int $requested): int
    {
        $today   = now()->startOfDay();
        $payouts = ZooCoinTransaction::where('user_id', $userId)
            ->whereIn('type', ['poker_ai_payout', 'blackjack_ai_payout'])
            ->where('created_at', '>=', $today)
            ->sum('amount');
        $bets = ZooCoinTransaction::where('user_id', $userId)
            ->whereIn('type', ['poker_ai_bet', 'blackjack_ai_bet'])
            ->where('created_at', '>=', $today)
            ->sum('amount');

        $netSoFar  = max(0, $payouts - $bets);
        $remaining = max(0, 5000 - $netSoFar);
        return min($requested, $remaining);
    }
}
