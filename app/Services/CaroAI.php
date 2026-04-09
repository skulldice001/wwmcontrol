<?php

namespace App\Services;

use App\Models\CaroGame;

class CaroAI
{
    private const RADIUS = 2; // candidate search radius around existing pieces

    /**
     * Return the best [row, col] for AI (symbol O) given difficulty.
     * difficulty: 'easy' | 'medium' | 'hard'
     */
    public static function getBestMove(CaroGame $game, string $difficulty): array
    {
        $board      = $game->boardDict();
        $candidates = self::getCandidates($board);

        // Empty board: play center
        if (empty($candidates)) {
            return [0, 0];
        }

        if ($difficulty === 'easy') {
            // Random candidate near existing pieces
            return $candidates[array_rand($candidates)];
        }

        // Score all candidates
        $scored = [];
        foreach ($candidates as $cell) {
            [$r, $c] = $cell;
            $scored[] = [
                'cell'  => $cell,
                'score' => self::scoreCell($board, $r, $c, 'O', 'X'),
            ];
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        if ($difficulty === 'medium') {
            // Weighted pick from top 3 — best move is most likely but not certain
            $topN    = min(3, count($scored));
            $weights = [5, 2, 1];
            $total   = array_sum(array_slice($weights, 0, $topN));
            $rand    = random_int(1, $total);
            $cum     = 0;
            for ($i = 0; $i < $topN; $i++) {
                $cum += $weights[$i];
                if ($rand <= $cum) return $scored[$i]['cell'];
            }
        }

        // Hard: always play the highest-scoring cell
        return $scored[0]['cell'];
    }

    // ── Candidate generation ──────────────────────────────────────────────────

    /** Return all empty cells within RADIUS of any existing piece. */
    private static function getCandidates(array $board): array
    {
        if (empty($board)) {
            return [[0, 0]];
        }

        $candidates = [];
        $seen       = [];

        foreach (array_keys($board) as $key) {
            [$r, $c] = explode(',', $key);
            $r = (int)$r; $c = (int)$c;

            for ($dr = -self::RADIUS; $dr <= self::RADIUS; $dr++) {
                for ($dc = -self::RADIUS; $dc <= self::RADIUS; $dc++) {
                    $nr = $r + $dr;
                    $nc = $c + $dc;
                    $nk = "{$nr},{$nc}";
                    if (!isset($board[$nk]) && !isset($seen[$nk])) {
                        $candidates[] = [$nr, $nc];
                        $seen[$nk]    = true;
                    }
                }
            }
        }

        return $candidates;
    }

    // ── Scoring ───────────────────────────────────────────────────────────────

    /** Score placing at (row, col): offensive + defensive across all 4 axes. */
    private static function scoreCell(array $board, int $row, int $col, string $aiSym, string $humanSym): int
    {
        $directions = [[0,1],[1,0],[1,1],[1,-1]];
        $score      = 0;

        foreach ($directions as [$dr, $dc]) {
            $aiCount    = self::countLine($board, $row, $col, $dr, $dc, $aiSym);
            $humanCount = self::countLine($board, $row, $col, $dr, $dc, $humanSym);
            $score += self::offensiveScore($aiCount);
            $score += self::defensiveScore($humanCount);
        }

        return $score;
    }

    /**
     * Count consecutive same-symbol stones in a line through (row,col),
     * treating (row,col) itself as the new AI stone (not yet placed).
     */
    private static function countLine(array $board, int $row, int $col, int $dr, int $dc, string $sym): int
    {
        $count = 1; // the target cell itself

        for ($i = 1; $i <= CaroEngine::WIN_LENGTH; $i++) {
            if (($board[($row + $dr*$i) . ',' . ($col + $dc*$i)] ?? null) === $sym) {
                $count++;
            } else {
                break;
            }
        }
        for ($i = 1; $i <= CaroEngine::WIN_LENGTH; $i++) {
            if (($board[($row - $dr*$i) . ',' . ($col - $dc*$i)] ?? null) === $sym) {
                $count++;
            } else {
                break;
            }
        }

        return min($count, CaroEngine::WIN_LENGTH);
    }

    private static function offensiveScore(int $count): int
    {
        return match ($count) {
            5       => 1_000_000, // winning move
            4       => 100_000,   // one away from win
            3       => 1_000,
            2       => 100,
            default => 10,
        };
    }

    private static function defensiveScore(int $count): int
    {
        return match ($count) {
            5       => 900_000,  // must block opponent's win
            4       => 80_000,
            3       => 500,
            2       => 50,
            default => 5,
        };
    }
}
