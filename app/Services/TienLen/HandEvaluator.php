<?php

namespace App\Services\TienLen;

/**
 * Evaluates Tiến Lên hands and determines if one combo beats another.
 *
 * Hand types:
 *   single     – 1 card
 *   pair       – 2 cards, same rank
 *   triple     – 3 cards, same rank
 *   quad       – 4 cards, same rank
 *   straight   – ≥3 consecutive ranks, no 2s
 *   pair_seq   – ≥2 consecutive pairs, no 2s (e.g. 3344, 334455)
 *
 * Miền Nam "chặt" (special beats):
 *   quad           beats single 2
 *   3+ pair_seq    beats single 2
 *   4+ pair_seq    beats pair of 2s
 */
class HandEvaluator
{
    /**
     * Classify a set of card integers.
     * Returns ['type' => ..., 'cards' => [...sorted], 'top' => highestCard]
     * or null if not a valid hand.
     */
    public static function classify(array $cards): ?array
    {
        if (empty($cards)) return null;
        sort($cards);
        $n = count($cards);

        $rankGroups = [];
        foreach ($cards as $c) {
            $r = Deck::rankIndex($c);
            $rankGroups[$r][] = $c;
        }
        $groupSizes = array_map('count', $rankGroups);

        // Single
        if ($n === 1) {
            return ['type' => 'single', 'cards' => $cards, 'top' => $cards[0]];
        }

        // Pair
        if ($n === 2 && count($rankGroups) === 1) {
            return ['type' => 'pair', 'cards' => $cards, 'top' => $cards[1]];
        }

        // Triple
        if ($n === 3 && count($rankGroups) === 1) {
            return ['type' => 'triple', 'cards' => $cards, 'top' => $cards[2]];
        }

        // Quad (tứ quý)
        if ($n === 4 && count($rankGroups) === 1) {
            return ['type' => 'quad', 'cards' => $cards, 'top' => $cards[3]];
        }

        // Straight (sảnh) – ≥3 consecutive ranks, no 2s
        if ($n >= 3 && count($rankGroups) === $n) {
            $ranks = array_keys($rankGroups);
            sort($ranks);
            // No 2s
            if (!in_array(12, $ranks)) {
                $consecutive = true;
                for ($i = 1; $i < count($ranks); $i++) {
                    if ($ranks[$i] !== $ranks[$i - 1] + 1) {
                        $consecutive = false;
                        break;
                    }
                }
                if ($consecutive) {
                    return ['type' => 'straight', 'cards' => $cards, 'top' => $cards[$n - 1]];
                }
            }
        }

        // Pair sequence (đôi thông) – ≥2 consecutive pairs, no 2s
        if ($n >= 4 && $n % 2 === 0) {
            $pairCount = count($rankGroups);
            if ($pairCount === $n / 2 && !in_array(2, array_unique($groupSizes))) {
                // all groups must be pairs
                if (array_sum($groupSizes) === $n && !in_array(1, $groupSizes) && !in_array(3, $groupSizes) && !in_array(4, $groupSizes)) {
                    $ranks = array_keys($rankGroups);
                    sort($ranks);
                    if (!in_array(12, $ranks)) {
                        $consecutive = true;
                        for ($i = 1; $i < count($ranks); $i++) {
                            if ($ranks[$i] !== $ranks[$i - 1] + 1) {
                                $consecutive = false;
                                break;
                            }
                        }
                        if ($consecutive) {
                            return ['type' => 'pair_seq', 'cards' => $cards, 'top' => $cards[$n - 1], 'pair_count' => $pairCount];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Can $new beat $current?
     * $new and $current are classify() results.
     * $variant: 'mien_nam' or 'mien_bac'
     * $current may be null (empty table — anything goes).
     */
    public static function beats(?array $current, array $new, string $variant = 'mien_nam'): bool
    {
        if ($current === null) return true; // table is empty, anything goes

        $ct = $current['type'];
        $nt = $new['type'];

        // Miền Nam chặt mechanics
        if ($variant === 'mien_nam') {
            // quad or 3+ pair_seq beats single 2
            if ($ct === 'single' && Deck::isTwo($current['top'])) {
                if ($nt === 'quad') return true;
                if ($nt === 'pair_seq' && $new['pair_count'] >= 3) return true;
            }
            // 4+ pair_seq beats pair of 2s
            if ($ct === 'pair' && Deck::isTwo($current['top'])) {
                if ($nt === 'pair_seq' && $new['pair_count'] >= 4) return true;
            }
        }

        // Types must match for normal beats
        if ($ct !== $nt) return false;

        // Same type: compare top card
        if ($ct === 'straight') {
            // Same length required
            if (count($new['cards']) !== count($current['cards'])) return false;
        }
        if ($ct === 'pair_seq') {
            // Same number of pairs required
            if ($new['pair_count'] !== $current['pair_count']) return false;
        }

        return $new['top'] > $current['top'];
    }

    /**
     * Find the index of the player who holds 3♠ (card value 0).
     */
    public static function findFirstPlayer(array $hands): int
    {
        foreach ($hands as $idx => $hand) {
            if (in_array(0, $hand)) return $idx;
        }
        return 0;
    }

    /**
     * Find the best single card to auto-play when forced to open with 3♠.
     * Returns [0] (the 3♠ alone).
     */
    public static function autoPlayFirstTurn(array $hand): array
    {
        return [0]; // just play 3♠
    }
}
