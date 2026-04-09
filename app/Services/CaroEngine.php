<?php

namespace App\Services;

use App\Models\CaroGame;
use App\Models\CaroTable;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Support\Facades\DB;

class CaroEngine
{
    public const TURN_SECONDS = 60;  // seconds per move
    public const WIN_LENGTH   = 5;   // stones in a row to win
    public const INITIAL_VIEW = 15;  // initial viewport half-size from center

    // ── Public API ────────────────────────────────────────────────────────────

    /** Start a new game. Deducts entry_fee from both players. */
    public static function startGame(CaroTable $table): CaroGame
    {
        return DB::transaction(function () use ($table) {
            if ($table->entry_fee > 0) {
                self::deductFee($table->playerX, $table->entry_fee, $table->id);
                self::deductFee($table->playerO, $table->entry_fee, $table->id);
            }

            // Initial viewport centred at (0,0): rows and cols from 0..(INITIAL_VIEW-1)
            $half = (int) (self::INITIAL_VIEW / 2);
            return CaroGame::create([
                'caro_table_id'  => $table->id,
                'moves'          => [],
                'min_row'        => -$half,
                'max_row'        => $half,
                'min_col'        => -$half,
                'max_col'        => $half,
                'current_player' => 'X',
                'moves_count'    => 0,
                'winner'         => null,
                'winning_cells'  => null,
                'turn_deadline'  => now()->addSeconds(self::TURN_SECONDS),
                'status'         => 'playing',
            ]);
        });
    }

    /** Make a move. Returns ['ok', 'error', 'game']. */
    public static function makeMove(CaroGame $game, int $userId, int $row, int $col): array
    {
        return DB::transaction(function () use ($game, $userId, $row, $col) {
            $game  = CaroGame::where('id', $game->id)->lockForUpdate()->first();
            $table = $game->table;

            if ($game->status !== 'playing') {
                return ['ok' => false, 'error' => 'Ván đã kết thúc.'];
            }

            $sym    = $game->current_player;
            $myId   = ($sym === 'X') ? $table->player_x_id : $table->player_o_id;

            if ($myId !== $userId) {
                return ['ok' => false, 'error' => 'Chưa đến lượt của bạn.'];
            }

            $board = $game->boardDict();
            $key   = "{$row},{$col}";

            if (isset($board[$key])) {
                return ['ok' => false, 'error' => 'Ô này đã có quân.'];
            }

            // Place piece
            $board[$key]  = $sym;
            $moves        = $game->moves;
            $moves[]      = [$row, $col, $sym];
            $movesCount   = $game->moves_count + 1;

            // Expand viewport if near edge (auto-expand 3 cells)
            $EXPAND      = 3;
            $minRow = min($game->min_row, $row - $EXPAND);
            $maxRow = max($game->max_row, $row + $EXPAND);
            $minCol = min($game->min_col, $col - $EXPAND);
            $maxCol = max($game->max_col, $col + $EXPAND);

            // Check win
            $winCells = self::checkWin($board, $row, $col, $sym);
            $winner   = null;
            $status   = 'playing';

            if ($winCells) {
                $winner = $sym;
                $status = 'finished';
                self::settleGame($table, $game, $sym);
            }
            // No draw for unlimited board (virtually impossible to fill)

            $nextPlayer   = ($sym === 'X') ? 'O' : 'X';
            $turnDeadline = ($status === 'playing')
                ? now()->addSeconds(self::TURN_SECONDS)
                : null;

            $game->update([
                'moves'          => $moves,
                'min_row'        => $minRow,
                'max_row'        => $maxRow,
                'min_col'        => $minCol,
                'max_col'        => $maxCol,
                'current_player' => $nextPlayer,
                'moves_count'    => $movesCount,
                'winner'         => $winner,
                'winning_cells'  => $winCells,
                'turn_deadline'  => $turnDeadline,
                'status'         => $status,
            ]);

            if ($status === 'finished') {
                $table->update(['status' => 'finished']);
            }

            return ['ok' => true, 'game' => $game->fresh()];
        });
    }

    /** Forfeit: a player left or timed out. */
    public static function forfeit(CaroGame $game, int $forfeitUserId): CaroGame
    {
        return DB::transaction(function () use ($game, $forfeitUserId) {
            $game  = CaroGame::where('id', $game->id)->lockForUpdate()->first();
            $table = $game->table;

            if ($game->status !== 'playing') return $game;

            $loserSym  = ($table->player_x_id === $forfeitUserId) ? 'X' : 'O';
            $winnerSym = ($loserSym === 'X') ? 'O' : 'X';

            self::settleGame($table, $game, $winnerSym);

            $game->update(['winner' => $winnerSym, 'status' => 'finished']);
            $table->update(['status' => 'finished']);

            return $game->fresh();
        });
    }

    /** Build client state array. */
    public static function clientState(CaroGame $game, int $viewerUserId): array
    {
        $table    = $game->table;
        $mySymbol = null;
        if ($table->player_x_id === $viewerUserId) $mySymbol = 'X';
        if ($table->player_o_id === $viewerUserId) $mySymbol = 'O';

        $secsLeft = null;
        if ($game->status === 'playing' && $game->turn_deadline) {
            $secsLeft = max(0, (int) now()->diffInSeconds($game->turn_deadline, false));
        }

        return [
            'game_id'        => $game->id,
            'moves'          => $game->moves,          // [[row, col, sym], ...]
            'min_row'        => $game->min_row,
            'max_row'        => $game->max_row,
            'min_col'        => $game->min_col,
            'max_col'        => $game->max_col,
            'current_player' => $game->current_player,
            'moves_count'    => $game->moves_count,
            'winner'         => $game->winner,
            'winning_cells'  => $game->winning_cells,
            'status'         => $game->status,
            'my_symbol'      => $mySymbol,
            'turn_deadline'  => $game->turn_deadline?->timestamp,
            'secs_left'      => $secsLeft,
            'player_x'       => ['id' => $table->player_x_id, 'name' => $table->playerX?->name],
            'player_o'       => ['id' => $table->player_o_id, 'name' => $table->playerO?->name],
        ];
    }

    // ── Win detection ─────────────────────────────────────────────────────────

    /**
     * Check if placing sym at (row, col) creates WIN_LENGTH in a row.
     * board: dict of "row,col" => sym.
     * Returns winning cell coordinates [[r,c], ...] or null.
     */
    public static function checkWin(array $board, int $row, int $col, string $sym): ?array
    {
        $directions = [[0,1],[1,0],[1,1],[1,-1]]; // →  ↓  ↘  ↙

        foreach ($directions as [$dr, $dc]) {
            $cells = [[$row, $col]];

            // Forward
            for ($i = 1; $i < self::WIN_LENGTH * 2; $i++) {
                $r = $row + $dr * $i;
                $c = $col + $dc * $i;
                if (($board["{$r},{$c}"] ?? null) !== $sym) break;
                $cells[] = [$r, $c];
            }
            // Backward
            for ($i = 1; $i < self::WIN_LENGTH * 2; $i++) {
                $r = $row - $dr * $i;
                $c = $col - $dc * $i;
                if (($board["{$r},{$c}"] ?? null) !== $sym) break;
                $cells[] = [$r, $c];
            }

            if (count($cells) >= self::WIN_LENGTH) {
                return $cells;
            }
        }

        return null;
    }

    // ── Settlement ────────────────────────────────────────────────────────────

    private static function settleGame(CaroTable $table, CaroGame $game, string $winnerSym): void
    {
        $fee = $table->entry_fee;
        if ($fee <= 0) return;

        $winnerId = ($winnerSym === 'X') ? $table->player_x_id : $table->player_o_id;
        $winner   = User::where('id', $winnerId)->lockForUpdate()->first();
        if (!$winner) return;

        $prize     = $fee * 2;
        $balBefore = $winner->z_coins;
        $winner->increment('z_coins', $prize);

        ZooCoinTransaction::create([
            'user_id'        => $winnerId,
            'type'           => 'caro_win',
            'amount'         => $prize,
            'balance_before' => $balBefore,
            'balance_after'  => $balBefore + $prize,
            'note'           => "Cờ caro: thắng (bàn #{$table->id})",
        ]);
    }

    private static function deductFee(User $user, int $fee, int $tableId): void
    {
        $balBefore = $user->z_coins;
        $user->decrement('z_coins', $fee);
        ZooCoinTransaction::create([
            'user_id'        => $user->id,
            'type'           => 'caro_bet',
            'amount'         => $fee,
            'balance_before' => $balBefore,
            'balance_after'  => $balBefore - $fee,
            'note'           => "Cờ caro: phí vào bàn (bàn #{$tableId})",
        ]);
    }
}
