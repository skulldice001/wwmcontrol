<?php

namespace App\Services\TienLen;

use App\Models\TienLenGame;
use App\Models\TienLenTable;
use App\Models\ZooCoinTransaction;
use App\Models\User;

class GameEngine
{
    public const AI_NAMES = ['Minh', 'Hoa', 'Tuấn', 'Lan'];

    /**
     * Start a new game for the table.
     * Deals 13 cards to each player (+ AI if ai_mode).
     */
    public static function startGame(TienLenTable $table): TienLenGame
    {
        $playerRecords = $table->tablePlayerRecords()->with('user')->orderBy('seat')->get();
        $humanPlayers  = $playerRecords->map(fn($p) => [
            'user_id'  => $p->user_id,
            'name'     => $p->user->name,
            'is_ai'    => false,
            'hand'     => [],
            'passed'   => false,
            'finished' => false,
            'rank'     => null, // finishing position
        ])->values()->toArray();

        $players = $humanPlayers;

        // Add AI opponents to fill to 4 players
        if ($table->is_ai_mode) {
            $aiNeeded = 4 - count($players);
            $aiNames  = self::AI_NAMES;
            shuffle($aiNames);
            for ($i = 0; $i < $aiNeeded; $i++) {
                $players[] = [
                    'user_id'  => null,
                    'name'     => ($aiNames[$i] ?? 'AI') . ' (AI)',
                    'is_ai'    => true,
                    'hand'     => [],
                    'passed'   => false,
                    'finished' => false,
                    'rank'     => null,
                ];
            }
        } else {
            // Fill remaining seats with AI for games started with < 4 players
            // (Only if exactly fewer than 4 real players — allow 2-4 real)
            // For now: require 4 human players (enforced in controller)
        }

        // Deal cards
        $hands = Deck::deal();
        foreach ($players as $idx => &$p) {
            sort($hands[$idx]);
            $p['hand'] = $hands[$idx];
        }
        unset($p);

        $firstPlayerIdx = HandEvaluator::findFirstPlayer(array_column($players, 'hand'));

        $state = [
            'variant'          => $table->variant,
            'phase'            => 'playing',
            'players'          => $players,
            'current_player'   => $firstPlayerIdx,
            'last_combo'       => null,  // last played combo classify() result
            'last_player_idx'  => null,
            'pass_count'       => 0,
            'round_starter'    => $firstPlayerIdx,
            'first_turn'       => true,  // first play of game, must include 3♠
            'winner_indices'   => [],
            'log'              => [],
            'turn_started_at'  => now()->toIso8601String(),
        ];

        $game = TienLenGame::create([
            'tienlen_table_id' => $table->id,
            'state'            => $state,
            'status'           => 'active',
        ]);

        $table->update(['status' => 'playing']);

        // Deduct entry fees from human players
        foreach ($players as $p) {
            if (!$p['is_ai'] && $p['user_id']) {
                self::deductEntryFee($p['user_id'], $table->entry_fee, $table->is_ai_mode);
            }
        }

        // Let AI take turns immediately if it's AI's turn first
        if ($players[$firstPlayerIdx]['is_ai']) {
            $game = self::runAI($game);
        }

        return $game->fresh();
    }

    /**
     * Process a human player's action.
     * $cards: array of card integers to play, or [] to pass.
     */
    public static function playCards(TienLenGame $game, int $userId, array $cards): array
    {
        $state   = $game->state;
        $players = $state['players'];
        $current = $state['current_player'];

        // Find the player index for this user
        $playerIdx = null;
        foreach ($players as $idx => $p) {
            if ($p['user_id'] === $userId) {
                $playerIdx = $idx;
                break;
            }
        }

        if ($playerIdx === null || $playerIdx !== $current) {
            return ['error' => 'Không phải lượt của bạn'];
        }

        $player = $players[$playerIdx];

        if (empty($cards)) {
            // Pass
            if ($state['last_combo'] === null) {
                return ['error' => 'Không thể bỏ lượt khi bàn trống'];
            }
            // Cannot pass on your own round start after everyone else passed
            $result = self::applyPass($state, $playerIdx);
        } else {
            // Validate cards are in hand
            foreach ($cards as $c) {
                if (!in_array($c, $player['hand'])) {
                    return ['error' => 'Bài không hợp lệ'];
                }
            }
            // Classify hand
            $combo = HandEvaluator::classify($cards);
            if ($combo === null) {
                return ['error' => 'Bộ bài không hợp lệ'];
            }

            // First turn: must include 3♠ (card 0)
            if ($state['first_turn'] && !in_array(0, $cards)) {
                return ['error' => 'Lượt đầu phải đánh bài có 3♠'];
            }

            // Must beat current combo
            if (!HandEvaluator::beats($state['last_combo'], $combo, $state['variant'])) {
                return ['error' => 'Bài không đủ mạnh để đánh'];
            }

            $result = self::applyPlay($state, $playerIdx, $cards, $combo);
        }

        $game->state = $result['state'];
        $game->status = $result['state']['phase'] === 'finished' ? 'finished' : 'active';
        $game->save();

        // If game over, settle Z-coins
        if ($game->status === 'finished') {
            self::settleZCoins($game);
            $game->table->update(['status' => 'finished']);
        }

        // Run AI turns if needed
        if ($game->status === 'active') {
            $newState = $game->state;
            if ($newState['players'][$newState['current_player']]['is_ai']) {
                $game = self::runAI($game);
            }
        }

        return ['ok' => true, 'game' => $game->fresh()];
    }

    private static function applyPlay(array $state, int $playerIdx, array $cards, array $combo): array
    {
        $players = $state['players'];

        // Remove played cards from hand
        $newHand = array_values(array_diff($players[$playerIdx]['hand'], $cards));
        $players[$playerIdx]['hand']   = $newHand;
        $players[$playerIdx]['passed'] = false;

        $cardLabels = array_map([Deck::class, 'label'], $cards);
        $state['log'][] = $players[$playerIdx]['name'] . ' đánh: ' . implode(' ', $cardLabels);

        $state['last_combo']      = $combo;
        $state['last_player_idx'] = $playerIdx;
        $state['first_turn']      = false;
        $state['pass_count']      = 0;

        // Check if this player finished
        if (empty($newHand)) {
            $finishRank = count($state['winner_indices']) + 1;
            $players[$playerIdx]['finished'] = true;
            $players[$playerIdx]['rank']     = $finishRank;
            $state['winner_indices'][]       = $playerIdx;
            $state['log'][] = $players[$playerIdx]['name'] . ' đã đánh hết bài! Hạng ' . $finishRank;
        }

        $state['players'] = $players;

        // Check if game is finished (≤1 player still playing)
        $activePlayers = array_filter($players, fn($p) => !$p['finished']);
        if (count($activePlayers) <= 1) {
            // Last player loses
            foreach ($players as $idx => $p) {
                if (!$p['finished']) {
                    $players[$idx]['finished'] = true;
                    $players[$idx]['rank']     = count($players);
                    $state['winner_indices'][] = $idx;
                }
            }
            $state['players'] = $players;
            $state['phase']   = 'finished';
            return ['state' => $state];
        }

        // Advance turn
        $state['current_player']  = self::nextActive($players, $playerIdx);
        $state['turn_started_at'] = now()->toIso8601String();

        return ['state' => $state];
    }

    private static function applyPass(array $state, int $playerIdx): array
    {
        $players = $state['players'];
        $players[$playerIdx]['passed'] = true;
        $state['log'][]   = $players[$playerIdx]['name'] . ' bỏ lượt';
        $state['players'] = $players;
        $state['pass_count']++;

        // Count active non-finished players
        $activePlayers = array_filter($players, fn($p) => !$p['finished']);
        $activeCount   = count($activePlayers);

        // If all other active players passed, the last_player_idx starts fresh
        if ($state['pass_count'] >= $activeCount - 1) {
            $state['last_combo']     = null;
            $state['last_player_idx'] = null;
            $state['pass_count']      = 0;
            $state['round_starter']   = $state['last_player_idx'] ?? self::nextActive($players, $playerIdx);
            // The winner of last round starts fresh
            $starter = $state['last_player_idx'] ?? self::nextActive($players, $playerIdx);
            // Reset passes
            foreach ($players as $idx => &$p) {
                $p['passed'] = false;
            }
            unset($p);
            $state['players']        = $players;
            $state['current_player'] = $starter;
        } else {
            $state['current_player'] = self::nextActive($players, $playerIdx);
        }

        $state['turn_started_at'] = now()->toIso8601String();
        return ['state' => $state];
    }

    /** Find next active (not-finished) player index after $from */
    private static function nextActive(array $players, int $from): int
    {
        $n = count($players);
        for ($i = 1; $i <= $n; $i++) {
            $idx = ($from + $i) % $n;
            if (!$players[$idx]['finished']) return $idx;
        }
        return $from;
    }

    /**
     * Run AI turns until it's a human player's turn or game over.
     */
    public static function runAI(TienLenGame $game): TienLenGame
    {
        $maxTurns = 200; // safety limit
        $turns = 0;

        while ($turns++ < $maxTurns) {
            $state   = $game->state;
            $current = $state['current_player'];

            if ($state['phase'] === 'finished') break;
            if (!$state['players'][$current]['is_ai']) break;

            $player = $state['players'][$current];
            $hand   = $player['hand'];

            // Find a valid combo to play
            $playedCombo = self::aiChooseCombo($hand, $state['last_combo'], $state['variant'], $state['first_turn']);

            if ($playedCombo === null) {
                // Pass
                $result = self::applyPass($state, $current);
            } else {
                $combo = HandEvaluator::classify($playedCombo);
                $result = self::applyPlay($state, $current, $playedCombo, $combo);
            }

            $game->state  = $result['state'];
            $game->status = $result['state']['phase'] === 'finished' ? 'finished' : 'active';
            $game->save();

            if ($game->status === 'finished') {
                self::settleZCoins($game);
                $game->table->update(['status' => 'finished']);
                break;
            }
        }

        return $game->fresh();
    }

    /**
     * Simple AI: find the lowest valid combo to play.
     * Returns array of card ints, or null to pass.
     */
    private static function aiChooseCombo(array $hand, ?array $lastCombo, string $variant, bool $firstTurn): ?array
    {
        sort($hand);

        // First turn: must include 3♠ (card 0)
        if ($firstTurn) {
            return [0]; // play 3♠ single
        }

        if ($lastCombo === null) {
            // Start fresh — play lowest single
            return [$hand[0]];
        }

        $type = $lastCombo['type'];

        // Try to beat with same type
        if ($type === 'single') {
            foreach ($hand as $c) {
                $candidate = HandEvaluator::classify([$c]);
                if ($candidate && HandEvaluator::beats($lastCombo, $candidate, $variant)) {
                    return [$c];
                }
            }
        }

        if ($type === 'pair') {
            $pairs = self::findPairs($hand);
            foreach ($pairs as $pair) {
                $candidate = HandEvaluator::classify($pair);
                if ($candidate && HandEvaluator::beats($lastCombo, $candidate, $variant)) {
                    return $pair;
                }
            }
        }

        if ($type === 'triple') {
            $triples = self::findNOfAKind($hand, 3);
            foreach ($triples as $t) {
                $candidate = HandEvaluator::classify($t);
                if ($candidate && HandEvaluator::beats($lastCombo, $candidate, $variant)) {
                    return $t;
                }
            }
        }

        if ($type === 'straight') {
            $len       = count($lastCombo['cards']);
            $straights = self::findStraights($hand, $len);
            foreach ($straights as $s) {
                $candidate = HandEvaluator::classify($s);
                if ($candidate && HandEvaluator::beats($lastCombo, $candidate, $variant)) {
                    return $s;
                }
            }
        }

        if ($type === 'pair_seq') {
            $pairCount = $lastCombo['pair_count'];
            $seqs = self::findPairSeqs($hand, $pairCount);
            foreach ($seqs as $s) {
                $candidate = HandEvaluator::classify($s);
                if ($candidate && HandEvaluator::beats($lastCombo, $candidate, $variant)) {
                    return $s;
                }
            }
        }

        // Miền Nam chặt: use quad/pair_seq to beat 2
        if ($variant === 'mien_nam') {
            if ($type === 'single' && Deck::isTwo($lastCombo['top'])) {
                $quads = self::findNOfAKind($hand, 4);
                if (!empty($quads)) return $quads[0];
            }
        }

        return null; // pass
    }

    private static function findPairs(array $hand): array
    {
        $groups = [];
        foreach ($hand as $c) {
            $r = Deck::rankIndex($c);
            $groups[$r][] = $c;
        }
        $pairs = [];
        foreach ($groups as $r => $cards) {
            if (count($cards) >= 2) {
                $pairs[] = array_slice($cards, 0, 2);
            }
        }
        return $pairs;
    }

    private static function findNOfAKind(array $hand, int $n): array
    {
        $groups = [];
        foreach ($hand as $c) {
            $r = Deck::rankIndex($c);
            $groups[$r][] = $c;
        }
        $result = [];
        foreach ($groups as $r => $cards) {
            if (count($cards) >= $n) {
                $result[] = array_slice($cards, 0, $n);
            }
        }
        return $result;
    }

    private static function findStraights(array $hand, int $len): array
    {
        // Find all valid straights of exactly $len cards from $hand
        $singlesByRank = [];
        foreach ($hand as $c) {
            $r = Deck::rankIndex($c);
            if ($r === 12) continue; // no 2s
            $singlesByRank[$r][] = $c;
        }
        $ranks = array_keys($singlesByRank);
        sort($ranks);

        $straights = [];
        $n = count($ranks);
        for ($i = 0; $i <= $n - $len; $i++) {
            $run = [$ranks[$i]];
            for ($j = $i + 1; $j < $n && count($run) < $len; $j++) {
                if ($ranks[$j] === end($run) + 1) {
                    $run[] = $ranks[$j];
                } else {
                    break;
                }
            }
            if (count($run) === $len) {
                $cards = [];
                foreach ($run as $r) {
                    $cards[] = $singlesByRank[$r][0]; // lowest suit
                }
                $straights[] = $cards;
            }
        }
        return $straights;
    }

    private static function findPairSeqs(array $hand, int $pairCount): array
    {
        $pairsByRank = [];
        foreach ($hand as $c) {
            $r = Deck::rankIndex($c);
            if ($r === 12) continue;
            $pairsByRank[$r][] = $c;
        }
        // Filter to ranks that have ≥2 cards
        $pairRanks = [];
        foreach ($pairsByRank as $r => $cards) {
            if (count($cards) >= 2) $pairRanks[] = $r;
        }
        sort($pairRanks);

        $seqs = [];
        $n = count($pairRanks);
        for ($i = 0; $i <= $n - $pairCount; $i++) {
            $run = [$pairRanks[$i]];
            for ($j = $i + 1; $j < $n && count($run) < $pairCount; $j++) {
                if ($pairRanks[$j] === end($run) + 1) {
                    $run[] = $pairRanks[$j];
                } else {
                    break;
                }
            }
            if (count($run) === $pairCount) {
                $cards = [];
                foreach ($run as $r) {
                    $cards = array_merge($cards, array_slice($pairsByRank[$r], 0, 2));
                }
                $seqs[] = $cards;
            }
        }
        return $seqs;
    }

    /**
     * Settle Z-coins after game.
     * Winner (rank 1) takes all entry fees minus a small house cut.
     * In AI mode: net profit ÷ 10, capped at 5000 daily.
     */
    public static function settleZCoins(TienLenGame $game): void
    {
        $table   = $game->table;
        $state   = $game->state;
        $players = $state['players'];
        $isAi    = $table->is_ai_mode;
        $fee     = $table->entry_fee;

        // Count human players
        $humanPlayers = array_filter($players, fn($p) => !$p['is_ai'] && $p['user_id']);

        $pot = $fee * count($humanPlayers);

        // Find winner (rank 1) among humans
        $winnerIdx = null;
        foreach ($players as $idx => $p) {
            if ($p['rank'] === 1 && !$p['is_ai'] && $p['user_id']) {
                $winnerIdx = $idx;
                break;
            }
        }

        if ($winnerIdx === null) return; // AI won, no payout

        $winner  = $players[$winnerIdx];
        $userId  = $winner['user_id'];
        $user    = User::find($userId);
        if (!$user) return;

        if ($isAi) {
            // Net gain = pot - fee_paid = (humanCount-1) * fee
            $netGain = $pot - $fee;
            $payout  = $fee + self::capAiPayout($userId, $netGain);
            $type    = 'tienlen_ai_payout';
        } else {
            $payout = $pot;
            $type   = 'tienlen_win';
        }

        $before = $user->z_coins;
        $after  = $before + $payout;
        $user->update(['z_coins' => $after]);

        ZooCoinTransaction::create([
            'user_id'        => $userId,
            'type'           => $type,
            'amount'         => $payout,
            'balance_before' => $before,
            'balance_after'  => $after,
            'note'           => 'Tiến Lên - thắng',
        ]);
    }

    private static function deductEntryFee(int $userId, int $fee, bool $isAi): void
    {
        $user = User::find($userId);
        if (!$user) return;

        $type   = $isAi ? 'tienlen_ai_bet' : 'tienlen_bet';
        $before = $user->z_coins;
        $after  = max(0, $before - $fee);
        $user->update(['z_coins' => $after]);

        ZooCoinTransaction::create([
            'user_id'        => $userId,
            'type'           => $type,
            'amount'         => $fee,
            'balance_before' => $before,
            'balance_after'  => $after,
            'note'           => 'Tiến Lên - phí tham gia',
        ]);
    }

    private static function capAiPayout(int $userId, int $requested): int
    {
        $today = now()->startOfDay();

        $payouts = ZooCoinTransaction::where('user_id', $userId)
            ->whereIn('type', ['poker_ai_payout', 'blackjack_ai_payout', 'tienlen_ai_payout'])
            ->where('created_at', '>=', $today)->sum('amount');

        $bets = ZooCoinTransaction::where('user_id', $userId)
            ->whereIn('type', ['poker_ai_bet', 'blackjack_ai_bet', 'tienlen_ai_bet'])
            ->where('created_at', '>=', $today)->sum('amount');

        $netSoFar  = max(0, $payouts - $bets);
        $remaining = max(0, 5000 - $netSoFar);

        return min($requested, $remaining);
    }
}
