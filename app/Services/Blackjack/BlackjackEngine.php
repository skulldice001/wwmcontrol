<?php

namespace App\Services\Blackjack;

use App\Models\BlackjackRound;
use App\Models\BlackjackTable;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Support\Facades\DB;

class BlackjackEngine
{
    const SUITS = ['spades', 'hearts', 'diamonds', 'clubs'];
    const RANKS = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

    // ── Public API ──────────────────────────────────────────────────────────

    /**
     * Start a new round: build initial state, transition to 'betting'.
     */
    public static function startRound(BlackjackTable $table): BlackjackRound
    {
        $pivotPlayers = $table->players()
            ->withPivot('role', 'seat', 'is_ready')
            ->get();

        // In AI mode, all seated users are players; dealer is the AI
        $isAiMode = (bool) $table->is_ai_mode;

        if ($isAiMode) {
            $dealer        = null; // AI dealer
            $playerRecords = $pivotPlayers->sortBy('pivot.seat');
        } else {
            $dealer        = $pivotPlayers->first(fn($p) => $p->pivot->role === 'dealer');
            $playerRecords = $pivotPlayers->where('pivot.role', 'player')->sortBy('pivot.seat');
        }

        // Build player slots ordered by seat
        $players   = [];
        $turnOrder = [];
        $seat      = 1;
        foreach ($playerRecords as $p) {
            $seatNum = $isAiMode ? $seat++ : (int) $p->pivot->seat;
            $players[(string) $p->id] = [
                'user_id'    => $p->id,
                'name'       => $p->name,
                'seat'       => $seatNum,
                'cards'      => [],
                'bet'        => 0,
                'bet_placed' => false,
                'can_double' => false,
                'stood'      => false,
                'busted'     => false,
                'blackjack'  => false,
                'result'     => null,
                'payout'     => 0,
            ];
            $turnOrder[] = $p->id;
        }

        $dealerName = $isAiMode ? 'Dealer (AI)' : ($dealer?->name ?? 'Dealer');
        $dealerUserId = $isAiMode ? 0 : ($dealer?->id ?? 0);

        $state = [
            'deck'                 => [],
            'is_ai_mode'           => $isAiMode,
            'dealer'               => [
                'user_id'     => $dealerUserId,
                'name'        => $dealerName,
                'is_ai'       => $isAiMode,
                'cards'       => [],
                'hole_card'   => null,
                'score'       => 0,
                'blackjack'   => false,
                'busted'      => false,
                'is_revealed' => false,
            ],
            'players'              => $players,
            'turn_order'           => $turnOrder,
            'current_turn_user_id' => null,
        ];

        return BlackjackRound::create([
            'blackjack_table_id'   => $table->id,
            'phase'                => 'betting',
            'state'                => $state,
            'current_turn_user_id' => null,
        ]);
    }

    /**
     * Player places their bet.
     */
    public static function placeBet(BlackjackRound $round, int $userId, int $bet): array
    {
        return DB::transaction(function () use ($round, $userId, $bet) {
            $round = BlackjackRound::where('id', $round->id)->lockForUpdate()->first();

            if ($round->phase !== 'betting') {
                return ['ok' => false, 'error' => 'Not in betting phase.'];
            }

            $state = $round->state;

            if (!isset($state['players'][(string) $userId])) {
                return ['ok' => false, 'error' => 'Not a player at this table.'];
            }
            if ($state['players'][(string) $userId]['bet_placed']) {
                return ['ok' => false, 'error' => 'Bet already placed.'];
            }

            $table = $round->table;
            if ($bet < $table->min_bet || $bet > $table->max_bet) {
                return ['ok' => false, 'error' => __('messages.bj_invalid_bet', [
                    ':min' => number_format($table->min_bet),
                    ':max' => number_format($table->max_bet),
                ])];
            }

            $user      = User::where('id', $userId)->lockForUpdate()->first();
            $available = $user->z_coins - $user->z_coins_frozen;
            if ($available < $bet) {
                return ['ok' => false, 'error' => __('messages.bj_insufficient_funds')];
            }

            // Deduct bet
            $isAiMode  = (bool) ($round->state['is_ai_mode'] ?? false);
            $balBefore = $user->z_coins;
            $user->decrement('z_coins', $bet);
            ZooCoinTransaction::create([
                'user_id'        => $userId,
                'type'           => $isAiMode ? 'blackjack_ai_bet' : 'blackjack_bet',
                'amount'         => $bet,
                'balance_before' => $balBefore,
                'balance_after'  => $balBefore - $bet,
                'note'           => "Blackjack" . ($isAiMode ? " vs AI" : "") . " đặt cược (bàn #{$table->id})",
            ]);

            $state['players'][(string) $userId]['bet']        = $bet;
            $state['players'][(string) $userId]['bet_placed'] = true;

            // Check if all players have bet
            $allBet = collect($state['players'])->every(fn($p) => $p['bet_placed']);

            $round->update(['state' => $state]);

            if ($allBet) {
                $round = self::dealCards($round);
            }

            return ['ok' => true, 'round' => $round->fresh()];
        });
    }

    /**
     * Deal cards in correct order:
     * Round 1: each player (seat order) gets 1 face-up, then dealer gets 1 face-up
     * Round 2: each player (seat order) gets 1 face-up, then dealer gets 1 face-down (hole card)
     */
    public static function dealCards(BlackjackRound $round): BlackjackRound
    {
        $state = $round->state;
        $deck  = self::freshDeck();

        $turnOrder = $state['turn_order'];

        // Round 1 — all players face-up, then dealer face-up
        foreach ($turnOrder as $uid) {
            $state['players'][(string) $uid]['cards'][] = array_shift($deck);
        }
        $state['dealer']['cards'][] = array_shift($deck); // dealer face-up

        // Round 2 — all players face-up, then dealer face-down (hole card)
        foreach ($turnOrder as $uid) {
            $state['players'][(string) $uid]['cards'][] = array_shift($deck);
        }
        $state['dealer']['hole_card'] = array_shift($deck); // hidden

        $state['deck'] = $deck;

        // Score players, detect blackjack
        $allDone = true;
        foreach ($state['turn_order'] as $uid) {
            $p      = &$state['players'][(string) $uid];
            $score  = self::score($p['cards']);
            $bj     = self::isBlackjack($p['cards']);
            $p['blackjack']  = $bj;
            $p['can_double'] = !$bj;
            if ($bj || $score > 21) {
                $p['stood'] = true;
            } else {
                $allDone = false;
            }
        }
        unset($p);

        // Dealer visible score (face-up card only)
        $state['dealer']['score'] = self::score($state['dealer']['cards']);

        if ($allDone) {
            // All players have blackjack or busted immediately — go to dealer turn
            $round->update(['state' => $state]);
            return self::initDealerTurn($round->fresh());
        }

        // Set first active player's turn
        $firstActive = self::nextActiveTurn($state, null);
        $state['phase']                = 'player_turns';
        $state['current_turn_user_id'] = $firstActive;

        $round->update([
            'phase'                => 'player_turns',
            'state'                => $state,
            'current_turn_user_id' => $firstActive,
        ]);

        return $round->fresh();
    }

    /**
     * Dealer manually hits or stands during dealer_turn phase.
     */
    public static function dealerAction(BlackjackRound $round, int $userId, string $action): array
    {
        return DB::transaction(function () use ($round, $userId, $action) {
            $round = BlackjackRound::where('id', $round->id)->lockForUpdate()->first();

            if ($round->phase !== 'dealer_turn') {
                return ['ok' => false, 'error' => 'Not in dealer turn phase.'];
            }

            $state = $round->state;

            if ((int) ($state['dealer']['user_id'] ?? 0) !== $userId) {
                return ['ok' => false, 'error' => 'Only the dealer can perform this action.'];
            }

            $score = self::score($state['dealer']['cards']);

            if ($action === 'hit') {
                if ($score >= 17) {
                    return ['ok' => false, 'error' => 'Dealer must stand at 17 or above.'];
                }
                $state['dealer']['cards'][] = array_shift($state['deck']);
                $newScore = self::score($state['dealer']['cards']);
                $state['dealer']['score'] = $newScore;
                if ($newScore > 21) {
                    $state['dealer']['busted'] = true;
                }
                $round->update(['state' => $state]);
                $round = $round->fresh();
                if ($state['dealer']['busted']) {
                    $round = self::resolveAll($round);
                }
            } elseif ($action === 'stand') {
                if ($score < 17) {
                    return ['ok' => false, 'error' => 'Dealer must hit below 17.'];
                }
                $state['dealer']['score'] = $score;
                $round->update(['state' => $state]);
                $round = self::resolveAll($round->fresh());
            }

            return ['ok' => true, 'round' => $round->fresh()];
        });
    }

    /**
     * Process player action: hit | stand | double
     */
    public static function processAction(BlackjackRound $round, int $userId, string $action): array
    {
        return DB::transaction(function () use ($round, $userId, $action) {
            $round = BlackjackRound::where('id', $round->id)->lockForUpdate()->first();

            if ($round->phase !== 'player_turns') {
                return ['ok' => false, 'error' => 'Not in player turns phase.'];
            }
            if ((int) $round->current_turn_user_id !== $userId) {
                return ['ok' => false, 'error' => 'Not your turn.'];
            }

            $state = $round->state;
            $p     = &$state['players'][(string) $userId];

            switch ($action) {
                case 'hit':
                    $p['cards'][]    = array_shift($state['deck']);
                    $p['can_double'] = false;
                    if (self::score($p['cards']) > 21) {
                        $p['busted'] = true;
                        $p['stood']  = true;
                    }
                    break;

                case 'stand':
                    $p['stood'] = true;
                    break;

                case 'double':
                    if (!$p['can_double']) {
                        return ['ok' => false, 'error' => 'Cannot double.'];
                    }
                    $user  = User::where('id', $userId)->lockForUpdate()->first();
                    $avail = $user->z_coins - $user->z_coins_frozen;
                    if ($avail >= $p['bet']) {
                        $extraBet  = $p['bet'];
                        $balBefore = $user->z_coins;
                        $user->decrement('z_coins', $extraBet);
                        ZooCoinTransaction::create([
                            'user_id'        => $userId,
                            'type'           => 'blackjack_bet',
                            'amount'         => $extraBet,
                            'balance_before' => $balBefore,
                            'balance_after'  => $balBefore - $extraBet,
                            'note'           => "Blackjack đôi (bàn #{$round->blackjack_table_id})",
                        ]);
                        $p['bet'] *= 2;
                    }
                    $p['cards'][]    = array_shift($state['deck']);
                    $p['can_double'] = false;
                    $p['stood']      = true; // double forces stand
                    if (self::score($p['cards']) > 21) {
                        $p['busted'] = true;
                    }
                    break;
            }
            unset($p);

            // Advance turn
            $state = self::advanceTurn($state);

            $round->update([
                'phase'                => $state['phase'],
                'state'                => $state,
                'current_turn_user_id' => $state['current_turn_user_id'],
            ]);
            $round = $round->fresh();

            // If we transitioned to dealer_turn, reveal hole card and wait for dealer actions
            if ($round->phase === 'dealer_turn') {
                $round = self::initDealerTurn($round);
            }

            return ['ok' => true, 'round' => $round->fresh()];
        });
    }

    /**
     * Build client-safe state for a specific viewer.
     * Dealer sees hole card; players see it only after phase = dealer_turn/finished.
     */
    public static function clientState(BlackjackRound $round, User $viewer): array
    {
        $s    = $round->fresh()->state;
        $isDealer = (int) ($s['dealer']['user_id'] ?? 0) === $viewer->id;

        $dealerCards = $s['dealer']['cards'];
        $holeRevealed = in_array($round->phase, ['dealer_turn', 'finished']);

        if ($holeRevealed || $isDealer) {
            // Show hole card
            if ($s['dealer']['hole_card']) {
                $dealerCards[] = $s['dealer']['hole_card'];
            }
        } else {
            // Hide hole card
            if ($s['dealer']['hole_card']) {
                $dealerCards[] = ['rank' => 'back', 'suit' => 'back'];
            }
        }

        $dealerScore = self::score(
            $holeRevealed || $isDealer
                ? array_merge($s['dealer']['cards'], array_filter([$s['dealer']['hole_card']]))
                : $s['dealer']['cards']
        );

        $fresh = $viewer->fresh();

        return [
            'round_id'             => $round->id,
            'phase'                => $round->phase,
            'dealer'               => array_merge($s['dealer'], [
                'cards'       => $dealerCards,
                'score'       => $dealerScore,
                'is_revealed' => $holeRevealed || $isDealer,
            ]),
            'players'              => array_values($s['players']),
            'turn_order'           => $s['turn_order'],
            'current_turn_user_id' => $round->current_turn_user_id,
            'my_balance'           => $fresh->z_coins - $fresh->z_coins_frozen,
        ];
    }

    /**
     * Auto-play AI dealer: hit until score >= 17, then stand and resolve.
     * Only valid in AI-mode tables during dealer_turn phase.
     */
    public static function runAiDealer(BlackjackRound $round): BlackjackRound
    {
        return DB::transaction(function () use ($round) {
            $round = BlackjackRound::where('id', $round->id)->lockForUpdate()->first();

            if ($round->phase !== 'dealer_turn') {
                return $round;
            }

            $state = $round->state;

            // Hit until 17+
            for ($i = 0; $i < 20; $i++) {
                $score = self::score($state['dealer']['cards']);
                if ($score >= 17) break;
                $state['dealer']['cards'][] = array_shift($state['deck']);
                $newScore = self::score($state['dealer']['cards']);
                $state['dealer']['score'] = $newScore;
                if ($newScore > 21) {
                    $state['dealer']['busted'] = true;
                    break;
                }
            }

            $state['dealer']['score'] = self::score($state['dealer']['cards']);
            $round->update(['state' => $state]);

            return self::resolveAll($round->fresh());
        });
    }

    // ── Leave handling ──────────────────────────────────────────────────────

    /**
     * Handle a player leaving mid-round.
     *
     * Dealer leaves  → cancel round, refund all player bets.
     * Player leaves  → forfeit (treated as bust, bet lost).
     *                  If it was their turn, advance.
     *                  If all remaining players are done, initiate dealer turn.
     */
    public static function handlePlayerLeave(BlackjackRound $round, int $userId, string $pivotRole): BlackjackRound
    {
        return DB::transaction(function () use ($round, $userId, $pivotRole) {
            $round = BlackjackRound::where('id', $round->id)->lockForUpdate()->first();

            if ($round->phase === 'finished') {
                return $round;
            }

            if ($pivotRole === 'dealer') {
                return self::cancelRound($round);
            }

            $state = $round->state;
            $uid   = (string) $userId;

            if (!isset($state['players'][$uid])) {
                return $round;
            }

            // Mark as forfeited: busted so resolveAll gives payout = 0
            if (!$state['players'][$uid]['bet_placed']) {
                // Hasn't bet yet — remove from turn order entirely so no cards are dealt
                $state['turn_order'] = array_values(
                    array_filter($state['turn_order'], fn($u) => $u !== $userId)
                );
                $state['players'][$uid]['bet_placed'] = true; // so allBet check passes
                $state['players'][$uid]['bet']        = 0;
            }
            $state['players'][$uid]['busted'] = true;
            $state['players'][$uid]['stood']  = true;

            // Betting phase: check if all remaining players have placed bets
            if ($round->phase === 'betting') {
                $allBet = collect($state['players'])->every(fn($p) => $p['bet_placed']);
                if ($allBet) {
                    $round->update(['state' => $state]);
                    return self::dealCards($round->fresh());
                }
                $round->update(['state' => $state]);
                return $round->fresh();
            }

            // Player_turns phase: advance if it was their turn
            if ($round->phase === 'player_turns' && (int) $round->current_turn_user_id === $userId) {
                $state = self::advanceTurn($state);
            }

            // If all remaining players are now stood/busted, move to dealer turn
            $allDone = empty($state['turn_order']) || collect($state['turn_order'])->every(
                fn($u) => $state['players'][(string) $u]['stood'] || $state['players'][(string) $u]['busted']
            );

            if ($allDone && in_array($round->phase, ['player_turns'])) {
                $state['phase'] = 'dealer_turn';
            }

            $round->update([
                'phase'                => $state['phase'],
                'state'                => $state,
                'current_turn_user_id' => $state['current_turn_user_id'] ?? null,
            ]);
            $round = $round->fresh();

            if ($round->phase === 'dealer_turn') {
                $round = self::initDealerTurn($round);
            }

            return $round->fresh();
        });
    }

    /**
     * Cancel an active round (dealer left or no players remain).
     * Refunds bets for players who had placed one and haven't been resolved yet.
     */
    private static function cancelRound(BlackjackRound $round): BlackjackRound
    {
        $state = $round->state;

        foreach ($state['players'] as $uid => &$p) {
            if ($p['result'] !== null) continue; // already resolved
            if ($p['bet'] > 0) {
                $user = User::where('id', $p['user_id'])->lockForUpdate()->first();
                if ($user) {
                    $balBefore = $user->z_coins;
                    $user->increment('z_coins', $p['bet']);
                    ZooCoinTransaction::create([
                        'user_id'        => $p['user_id'],
                        'type'           => 'blackjack_payout',
                        'amount'         => $p['bet'],
                        'balance_before' => $balBefore,
                        'balance_after'  => $balBefore + $p['bet'],
                        'note'           => "Blackjack hoàn tiền (ván bị hủy, bàn #{$round->blackjack_table_id})",
                    ]);
                }
            }
            $p['result'] = 'push';
            $p['payout'] = $p['bet'];
        }
        unset($p);

        $state['phase'] = 'finished';
        $round->update([
            'phase'                => 'finished',
            'state'                => $state,
            'current_turn_user_id' => null,
        ]);

        return $round->fresh();
    }

    // ── Private helpers ─────────────────────────────────────────────────────

    private static function initDealerTurn(BlackjackRound $round): BlackjackRound
    {
        $state = $round->state;

        // Reveal hole card so all players can see it
        if ($state['dealer']['hole_card']) {
            $state['dealer']['cards'][]   = $state['dealer']['hole_card'];
            $state['dealer']['hole_card'] = null;
        }
        $state['dealer']['is_revealed'] = true;

        // Check dealer blackjack
        if (count($state['dealer']['cards']) === 2 && self::isBlackjack($state['dealer']['cards'])) {
            $state['dealer']['blackjack'] = true;
        }

        $state['dealer']['score'] = self::score($state['dealer']['cards']);
        $state['phase']           = 'dealer_turn';

        $round->update([
            'phase'                => 'dealer_turn',
            'state'                => $state,
            'current_turn_user_id' => null,
        ]);

        // Dealer blackjack: resolve immediately without manual action
        if ($state['dealer']['blackjack']) {
            return self::resolveAll($round->fresh());
        }

        return $round->fresh();
    }

    private static function resolveAll(BlackjackRound $round): BlackjackRound
    {
        $state       = $round->state;
        $isAiMode    = (bool) ($state['is_ai_mode'] ?? false);
        $dealerScore = self::score($state['dealer']['cards']);
        $dealerBj    = $state['dealer']['blackjack'] ?? false;
        $dealerBust  = $state['dealer']['busted']    ?? false;

        foreach ($state['players'] as $uid => &$p) {
            $playerScore = self::score($p['cards']);
            $playerBj    = $p['blackjack'];
            $bet         = $p['bet'];

            if ($p['busted']) {
                [$result, $payout] = ['bust', 0];
            } elseif ($playerBj && $dealerBj) {
                [$result, $payout] = ['push', $bet];
            } elseif ($playerBj) {
                [$result, $payout] = ['blackjack', $bet + (int) round($bet * 1.5)]; // 3:2
            } elseif ($dealerBj) {
                [$result, $payout] = ['lose', 0];
            } elseif ($dealerBust) {
                [$result, $payout] = ['dealer_bust', $bet * 2];
            } elseif ($playerScore > $dealerScore) {
                [$result, $payout] = ['win', $bet * 2];
            } elseif ($playerScore === $dealerScore) {
                [$result, $payout] = ['push', $bet];
            } else {
                [$result, $payout] = ['lose', 0];
            }

            // AI mode: scale payout by 0.1 (return bet unchanged if push/win, but profit portion ÷10)
            if ($isAiMode && $payout > 0) {
                $profit = $payout - $bet; // profit portion
                $payout = $bet + (int) floor($profit / 10); // return bet + scaled profit
                $payout = self::capAiPayout($p['user_id'], $payout, $bet, $round->blackjack_table_id);
            }

            $p['result'] = $result;
            $p['payout'] = $payout;

            // Credit payout
            if ($payout > 0) {
                DB::transaction(function () use ($p, $payout, $round, $result, $isAiMode) {
                    $user      = User::where('id', $p['user_id'])->lockForUpdate()->first();
                    $balBefore = $user->z_coins;
                    $user->increment('z_coins', $payout);
                    ZooCoinTransaction::create([
                        'user_id'        => $p['user_id'],
                        'type'           => $isAiMode ? 'blackjack_ai_payout' : 'blackjack_payout',
                        'amount'         => $payout,
                        'balance_before' => $balBefore,
                        'balance_after'  => $balBefore + $payout,
                        'note'           => "Blackjack" . ($isAiMode ? " vs AI" : "") . ": {$result} +{$payout} Zoo (bàn #{$round->blackjack_table_id})",
                    ]);
                });
            }
        }
        unset($p);

        $state['phase'] = 'finished';

        $round->update([
            'phase'                => 'finished',
            'state'                => $state,
            'current_turn_user_id' => null,
        ]);

        return $round->fresh();
    }

    private static function advanceTurn(array $state): array
    {
        $order   = $state['turn_order'];
        $current = $state['current_turn_user_id'];
        $next    = self::nextActiveTurn($state, $current);

        $state['current_turn_user_id'] = $next;

        if ($next === null) {
            $state['phase'] = 'dealer_turn';
        }

        return $state;
    }

    private static function nextActiveTurn(array $state, ?int $afterUserId): ?int
    {
        $order = $state['turn_order'];
        $start = $afterUserId === null ? 0 : array_search($afterUserId, $order) + 1;

        for ($i = $start; $i < count($order); $i++) {
            $uid = $order[$i];
            $p   = $state['players'][(string) $uid];
            if (!$p['stood'] && !$p['busted']) {
                return $uid;
            }
        }
        return null;
    }

    /** Cap AI payout so total daily net AI winnings don't exceed 5 000 Zoo. */
    private static function capAiPayout(int $userId, int $payout, int $bet, int $tableId): int
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
        // Profit from this payout = payout - bet (bet was already deducted earlier)
        $profit    = max(0, $payout - $bet);
        $remaining = max(0, 5000 - $netSoFar);
        $cappedProfit = min($profit, $remaining);
        return $bet + $cappedProfit; // always return at least the bet amount
    }

    private static function freshDeck(): array
    {
        $deck = [];
        foreach (self::SUITS as $suit) {
            foreach (self::RANKS as $rank) {
                $deck[] = ['rank' => $rank, 'suit' => $suit];
            }
        }
        shuffle($deck);
        return $deck;
    }

    public static function score(array $cards): int
    {
        $total = $aces = 0;
        foreach ($cards as $c) {
            if (($c['suit'] ?? '') === 'back') continue;
            $r = $c['rank'];
            if ($r === 'A') {
                $aces++;
                $total += 11;
            } elseif (in_array($r, ['J', 'Q', 'K', '10'])) {
                $total += 10;
            } else {
                $total += (int) $r;
            }
        }
        while ($total > 21 && $aces > 0) {
            $total -= 10;
            $aces--;
        }
        return $total;
    }

    private static function isBlackjack(array $cards): bool
    {
        return count($cards) === 2 && self::score($cards) === 21;
    }
}
