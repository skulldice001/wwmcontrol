<?php

namespace App\Services\Poker;

class HandEvaluator
{
    /**
     * Evaluate the best 5-card hand from hole cards + community cards.
     * Returns ['value' => int, 'name' => string]
     */
    public static function evaluate(array $holeCards, array $communityCards): array
    {
        $all = array_merge($holeCards, $communityCards);
        $k   = min(5, count($all));

        if ($k < 5) {
            return self::score(array_slice($all, 0, $k));
        }

        $best = null;
        foreach (self::combinations($all, 5) as $combo) {
            $result = self::score($combo);
            if ($best === null || $result['value'] > $best['value']) {
                $best = $result;
            }
        }
        return $best;
    }

    // -------------------------------------------------------------------------

    private static function score(array $cards): array
    {
        usort($cards, fn($a, $b) => Deck::VALUES[$b['rank']] - Deck::VALUES[$a['rank']]);

        $ranks = array_map(fn($c) => Deck::VALUES[$c['rank']], $cards);
        $suits = array_map(fn($c) => $c['suit'], $cards);

        $rankCounts = array_count_values($ranks);
        arsort($rankCounts);
        $cv = array_values($rankCounts); // count values: e.g. [3,2] for full house
        $ck = array_keys($rankCounts);   // rank values sorted by count desc

        $flush    = count($cards) === 5 && count(array_unique($suits)) === 1;
        $straight = count($cards) === 5 && self::isStraight($ranks);

        // Royal / Straight Flush
        if ($flush && $straight) {
            $top = max($ranks);
            if ($top === 14 && in_array(13, $ranks)) {
                return ['value' => 9_000_000 + $top, 'name' => 'Royal Flush'];
            }
            return ['value' => 8_000_000 + $top, 'name' => 'Straight Flush'];
        }

        // Four of a Kind
        if (($cv[0] ?? 0) === 4) {
            return ['value' => 7_000_000 + $ck[0] * 100 + ($ck[1] ?? 0), 'name' => 'Four of a Kind'];
        }

        // Full House
        if (($cv[0] ?? 0) === 3 && ($cv[1] ?? 0) === 2) {
            return ['value' => 6_000_000 + $ck[0] * 100 + $ck[1], 'name' => 'Full House'];
        }

        // Flush
        if ($flush) {
            $v = 5_000_000;
            foreach (array_slice($ranks, 0, 5) as $i => $r) {
                $v += $r * (int) (100 ** (4 - $i));
            }
            return ['value' => $v, 'name' => 'Flush'];
        }

        // Straight
        if ($straight) {
            return ['value' => 4_000_000 + max($ranks), 'name' => 'Straight'];
        }

        // Three of a Kind
        if (($cv[0] ?? 0) === 3) {
            return ['value' => 3_000_000 + $ck[0] * 10000, 'name' => 'Three of a Kind'];
        }

        // Two Pair
        if (($cv[0] ?? 0) === 2 && ($cv[1] ?? 0) === 2) {
            $kicker = array_diff($ranks, [$ck[0], $ck[1]]);
            return [
                'value' => 2_000_000 + max($ck[0], $ck[1]) * 10000 + min($ck[0], $ck[1]) * 100 + (max($kicker) ?: 0),
                'name'  => 'Two Pair',
            ];
        }

        // One Pair
        if (($cv[0] ?? 0) === 2) {
            $kickers = array_values(array_diff($ranks, [$ck[0]]));
            rsort($kickers);
            $v = 1_000_000 + $ck[0] * 100000;
            foreach (array_slice($kickers, 0, 3) as $i => $r) {
                $v += $r * (int) (100 ** (2 - $i));
            }
            return ['value' => $v, 'name' => 'One Pair'];
        }

        // High Card
        $v = 0;
        foreach (array_slice($ranks, 0, 5) as $i => $r) {
            $v += $r * (int) (100 ** (4 - $i));
        }
        return ['value' => $v, 'name' => 'High Card'];
    }

    private static function isStraight(array $ranks): bool
    {
        $u = array_unique($ranks);
        if (count($u) !== 5) return false;
        sort($u);
        if ($u[4] - $u[0] === 4) return true;
        // Wheel: A-2-3-4-5
        return $u === [2, 3, 4, 5, 14];
    }

    private static function combinations(array $arr, int $k): array
    {
        $n       = count($arr);
        $indices = range(0, $k - 1);
        $results = [array_map(fn($i) => $arr[$i], $indices)];

        while (true) {
            $i = $k - 1;
            while ($i >= 0 && $indices[$i] === $i + $n - $k) {
                $i--;
            }
            if ($i < 0) break;
            $indices[$i]++;
            for ($j = $i + 1; $j < $k; $j++) {
                $indices[$j] = $indices[$j - 1] + 1;
            }
            $results[] = array_map(fn($idx) => $arr[$idx], $indices);
        }
        return $results;
    }
}
