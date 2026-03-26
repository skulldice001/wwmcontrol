<?php

namespace App\Services\Poker;

use App\Models\PokerGame;
use App\Models\PokerTable;
use App\Models\User;

class GameEngine
{
    const MAX_SEATS  = 6;
    const AI_NAMES   = ['Alex', 'Bob', 'Carol', 'Dave', 'Eve', 'Frank', 'Grace', 'Hank'];
    const HAND_STRENGTH = [
        'Royal Flush'     => 1.00,
        'Straight Flush'  => 0.97,
        'Four of a Kind'  => 0.94,
        'Full House'      => 0.88,
        'Flush'           => 0.80,
        'Straight'        => 0.73,
        'Three of a Kind' => 0.64,
        'Two Pair'        => 0.53,
        'One Pair'        => 0.38,
        'High Card'       => 0.12,
    ];

    // =========================================================================
    //  Public API
    // =========================================================================

    /**
     * Start a new game.
     * $humanUserIds: array of real user IDs seated at the table.
     * Remaining seats are filled with AI bots.
     */
    public static function startGame(PokerTable $table, array $humanUserIds): PokerGame
    {
        PokerGame::where('poker_table_id', $table->id)->delete();

        $startChips = (int) $table->max_buy_in;

        // --- Build player seats: humans only, no AI bots ---
        $users   = User::whereIn('id', $humanUserIds)->get()->keyBy('id');
        $players = [];
        foreach ($humanUserIds as $uid) {
            $name      = $users[$uid]->name ?? "Player#{$uid}";
            $players[] = self::makePlayer($uid, $name, $startChips, false);
        }

        // --- Deal hole cards ---
        $deck = Deck::fresh();
        foreach ($players as &$p) {
            $p['hole_cards'] = Deck::dealN($deck, 2);
        }
        unset($p);

        // --- Blind positions ---
        $n         = count($players);
        $dealerIdx = 0;
        $sbIdx     = $n === 2 ? 0 : 1; // heads-up: dealer = SB
        $bbIdx     = $n === 2 ? 1 : 2;

        // Post blinds
        $sb = self::postBlind($players, $sbIdx, (float) $table->small_blind);
        $bb = self::postBlind($players, $bbIdx, (float) $table->big_blind);

        // First to act pre-flop is player after BB
        $firstToAct = ($bbIdx + 1) % $n;
        // BB still gets option (pending = true even after posting)
        foreach ($players as &$p) {
            $p['pending'] = $p['status'] === 'active';
        }
        unset($p);

        $state = [
            'phase'           => 'preflop',
            'deck'            => $deck,
            'community_cards' => [],
            'pot'             => $sb + $bb,
            'current_bet'     => (float) $table->big_blind,
            'last_raise'      => (float) $table->big_blind,
            'dealer_index'    => $dealerIdx,
            'sb_index'        => $sbIdx,
            'bb_index'        => $bbIdx,
            'current_player'  => $firstToAct,
            'turn_started_at' => time(),
            'players'         => $players,
            'winner_info'     => null,
            'small_blind'     => (float) $table->small_blind,
            'big_blind'       => (float) $table->big_blind,
            'log'             => ["New hand started. Blinds: {$table->small_blind}/{$table->big_blind}"],
        ];

        $game = PokerGame::create([
            'poker_table_id' => $table->id,
            'state'          => $state,
        ]);

        return $game;
    }

    public static function processAction(PokerGame $game, string $action, float $amount, int $userId): PokerGame
    {
        $state = $game->state;
        $idx   = $state['current_player'];
        $p     = $state['players'][$idx];

        // Guard: must be the correct human player's turn
        if ($p['is_ai'] || $p['id'] !== $userId || $p['status'] !== 'active' || !$p['pending']) {
            return $game;
        }

        $state = self::applyAction($state, $idx, $action, $amount);
        $state = self::advance($state);
        $game->update(['state' => $state]);

        return $game->fresh();
    }

    // =========================================================================
    //  Game flow
    // =========================================================================

    private static function applyAction(array $state, int $idx, string $action, float $amount): array
    {
        $p      = &$state['players'][$idx];
        $toCall = max(0.0, $state['current_bet'] - $p['bet']);

        switch ($action) {
            case 'fold':
                $p['status']  = 'folded';
                $p['pending'] = false;
                $state['log'][] = "{$p['name']} folds.";
                break;

            case 'check':
                $p['pending'] = false;
                $state['log'][] = "{$p['name']} checks.";
                break;

            case 'call':
                $actual = min($toCall, $p['chips']);
                $p['chips']     -= $actual;
                $p['bet']       += $actual;
                $p['total_bet'] += $actual;
                $state['pot']   += $actual;
                $p['pending']    = false;
                if ($p['chips'] <= 0) $p['status'] = 'all-in';
                $state['log'][] = "{$p['name']} calls " . number_format($actual) . ".";
                break;

            case 'raise':
                $minTotal = $state['current_bet'] + max($state['last_raise'], $state['big_blind']);
                $newTotal = max($amount, $minTotal);
                $toAdd    = min($newTotal - $p['bet'], $p['chips']);
                $newTotal = $p['bet'] + $toAdd;

                $state['last_raise']  = max($newTotal - $state['current_bet'], $state['big_blind']);
                $state['current_bet'] = $newTotal;
                $p['chips']          -= $toAdd;
                $p['bet']            += $toAdd;
                $p['total_bet']      += $toAdd;
                $state['pot']        += $toAdd;
                $p['pending']         = false;
                if ($p['chips'] <= 0) $p['status'] = 'all-in';

                // Re-open betting for other active players
                foreach ($state['players'] as $i => &$other) {
                    if ($i !== $idx && $other['status'] === 'active') {
                        $other['pending'] = true;
                    }
                }
                unset($other);
                $state['log'][] = "{$p['name']} raises to " . number_format($newTotal) . ".";
                break;

            case 'allin':
                $toAdd    = $p['chips'];
                $newTotal = $p['bet'] + $toAdd;
                if ($newTotal > $state['current_bet']) {
                    $state['last_raise']  = max($newTotal - $state['current_bet'], $state['big_blind']);
                    $state['current_bet'] = $newTotal;
                    foreach ($state['players'] as $i => &$other) {
                        if ($i !== $idx && $other['status'] === 'active') {
                            $other['pending'] = true;
                        }
                    }
                    unset($other);
                }
                $p['chips']     -= $toAdd;
                $p['bet']       += $toAdd;
                $p['total_bet'] += $toAdd;
                $state['pot']   += $toAdd;
                $p['status']     = 'all-in';
                $p['pending']    = false;
                $state['log'][] = "{$p['name']} goes all-in with " . number_format($newTotal) . "!";
                break;
        }

        unset($p);
        return $state;
    }

    /** Move to next player, or advance phase if round is complete */
    private static function advance(array $state): array
    {
        // Only 1 non-folded player left → go to showdown immediately
        $standing = array_filter($state['players'], fn($p) => $p['status'] !== 'folded');
        if (count($standing) <= 1) {
            return self::showdown($state);
        }

        // Check if any active player is still pending
        $anyPending = false;
        foreach ($state['players'] as $p) {
            if ($p['status'] === 'active' && $p['pending']) {
                $anyPending = true;
                break;
            }
        }

        if (!$anyPending) {
            return self::nextPhase($state);
        }

        // Find the next pending active player after current
        $n    = count($state['players']);
        $next = ($state['current_player'] + 1) % $n;
        for ($i = 0; $i < $n; $i++) {
            $p = $state['players'][$next];
            if ($p['status'] === 'active' && $p['pending']) {
                $state['current_player']  = $next;
                $state['turn_started_at'] = time();
                return $state;
            }
            $next = ($next + 1) % $n;
        }

        // No pending player found — advance phase
        return self::nextPhase($state);
    }

    private static function nextPhase(array $state): array
    {
        $order = ['preflop', 'flop', 'turn', 'river', 'showdown'];
        $curr  = array_search($state['phase'], $order);
        $next  = $order[$curr + 1] ?? 'showdown';

        // Reset per-round bets and pending flags
        foreach ($state['players'] as &$p) {
            $p['bet'] = 0.0;
            if ($p['status'] === 'active') $p['pending'] = true;
        }
        unset($p);
        $state['current_bet'] = 0.0;
        $state['last_raise']  = $state['big_blind'];
        $state['phase']       = $next;

        // Deal community cards
        switch ($next) {
            case 'flop':
                $state['community_cards'][] = Deck::deal($state['deck']);
                $state['community_cards'][] = Deck::deal($state['deck']);
                $state['community_cards'][] = Deck::deal($state['deck']);
                $state['log'][] = 'Flop dealt.';
                break;
            case 'turn':
                $state['community_cards'][] = Deck::deal($state['deck']);
                $state['log'][] = 'Turn dealt.';
                break;
            case 'river':
                $state['community_cards'][] = Deck::deal($state['deck']);
                $state['log'][] = 'River dealt.';
                break;
            case 'showdown':
                return self::showdown($state);
        }

        // If no active players remain (all all-in), skip to showdown
        $activeCount = count(array_filter($state['players'], fn($p) => $p['status'] === 'active'));
        if ($activeCount === 0) {
            return self::nextPhase($state); // recurse until showdown
        }

        // First to act: first active player left of dealer
        $n     = count($state['players']);
        $start = ($state['dealer_index'] + 1) % $n;
        for ($i = 0; $i < $n; $i++) {
            if ($state['players'][$start]['status'] === 'active') {
                $state['current_player']  = $start;
                $state['turn_started_at'] = time();
                break;
            }
            $start = ($start + 1) % $n;
        }

        return $state;
    }

    private static function showdown(array $state): array
    {
        $state['phase'] = 'showdown';

        // Evaluate all non-folded players
        $evals = [];
        foreach ($state['players'] as $idx => $p) {
            if ($p['status'] === 'folded') continue;
            $ev = HandEvaluator::evaluate($p['hole_cards'], $state['community_cards']);
            $evals[$idx] = $ev;
        }

        if (empty($evals)) {
            return $state;
        }

        // Find highest hand value
        $topValue = max(array_column($evals, 'value'));
        $winners  = array_filter($evals, fn($e) => $e['value'] === $topValue);

        // Award pot evenly
        $share     = (int) floor($state['pot'] / count($winners));
        $remainder = (int) $state['pot'] - $share * count($winners);

        $winnerIndices = array_keys($winners);
        foreach ($winnerIndices as $i => $wIdx) {
            $state['players'][$wIdx]['chips'] += $share + ($i === 0 ? $remainder : 0);
        }

        $winnerNames = array_map(fn($wIdx) => $state['players'][$wIdx]['name'], $winnerIndices);
        $handName    = $evals[$winnerIndices[0]]['name'];

        $state['winner_info'] = [
            'player_indices' => $winnerIndices,
            'names'          => array_values($winnerNames),
            'hand_name'      => $handName,
            'pot'            => $state['pot'],
            'folded_win'     => count($evals) === 1,
        ];

        $whoWon = implode(' & ', $winnerNames);
        $state['log'][] = "{$whoWon} wins " . number_format($state['pot']) . " with {$handName}!";

        return $state;
    }

    // =========================================================================
    //  AI
    // =========================================================================

    private static function runAI(PokerGame $game): PokerGame
    {
        for ($iter = 0; $iter < 50; $iter++) {
            $state = $game->state;

            if ($state['phase'] === 'showdown') break;

            $idx = $state['current_player'];
            $p   = $state['players'][$idx];

            if (!$p['is_ai'] || $p['status'] !== 'active' || !$p['pending']) break;

            [$action, $amount] = self::aiDecide($state, $idx);
            $state = self::applyAction($state, $idx, $action, $amount);
            $state = self::advance($state);
            $game->update(['state' => $state]);
        }

        return $game->fresh();
    }

    private static function aiDecide(array $state, int $idx): array
    {
        $p        = $state['players'][$idx];
        $toCall   = max(0.0, $state['current_bet'] - $p['bet']);
        $strength = self::handStrength($p['hole_cards'], $state['community_cards']);

        // No bet to call — check or bet
        if ($toCall <= 0) {
            if ($strength >= 0.68 && rand(1, 100) <= 65) {
                $raise = $state['current_bet'] + $state['big_blind'] * rand(2, 4);
                return ['raise', $raise];
            }
            return ['check', 0];
        }

        $callRatio = $toCall / max($p['chips'] + $p['bet'], 1);

        if ($strength >= 0.80) {
            return ['raise', $state['current_bet'] + $state['big_blind'] * rand(2, 6)];
        }
        if ($strength >= 0.50 || $callRatio <= 0.08) {
            return ['call', 0];
        }
        if ($strength >= 0.32 && rand(1, 100) <= 22) {
            return ['call', 0]; // occasional bluff
        }
        return ['fold', 0];
    }

    private static function handStrength(array $hole, array $community): float
    {
        if (empty($community)) {
            return self::preflopStrength($hole);
        }
        $eval = HandEvaluator::evaluate($hole, $community);
        return self::HAND_STRENGTH[$eval['name']] ?? 0.12;
    }

    private static function preflopStrength(array $hole): float
    {
        $v  = Deck::VALUES;
        $r1 = $v[$hole[0]['rank']];
        $r2 = $v[$hole[1]['rank']];
        $hi = max($r1, $r2);
        $lo = min($r1, $r2);
        $s  = ($hi + $lo) / 28.0;
        if ($r1 === $r2)                              $s = min(1.0, $s + 0.35); // pocket pair
        if ($hole[0]['suit'] === $hole[1]['suit'])    $s = min(1.0, $s + 0.08); // suited
        if ($hi - $lo <= 2)                           $s = min(1.0, $s + 0.05); // connected
        return $s;
    }

    // =========================================================================
    //  Helpers
    // =========================================================================

    private static function makePlayer(?int $id, string $name, int $chips, bool $isAI): array
    {
        return [
            'id'        => $id,
            'name'      => $name,
            'chips'     => $chips,
            'hole_cards'=> [],
            'bet'       => 0.0,
            'total_bet' => 0.0,
            'status'    => 'active',
            'pending'   => true,
            'is_ai'     => $isAI,
        ];
    }

    private static function postBlind(array &$players, int $idx, float $amount): float
    {
        $actual = min($amount, $players[$idx]['chips']);
        $players[$idx]['chips']     -= $actual;
        $players[$idx]['bet']        = $actual;
        $players[$idx]['total_bet']  = $actual;
        if ($players[$idx]['chips'] <= 0) {
            $players[$idx]['status'] = 'all-in';
        }
        return $actual;
    }
}
