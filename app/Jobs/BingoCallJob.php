<?php

namespace App\Jobs;

use App\Events\BingoRoomUpdated;
use App\Models\BingoGame;
use App\Models\BingoTable;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BingoCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $tableId,
        private int $gameId,
        private int $currentIndex,
        private int $callStartedAt,
    ) {}

    public function handle(): void
    {
        $game = BingoGame::find($this->gameId);
        if (!$game) return;

        $state = $game->state;

        // Guard: must still be playing, same index and timestamp
        if ($state['phase'] !== 'playing') return;
        if ($state['current_index'] !== $this->currentIndex) return;
        if (($state['call_started_at'] ?? 0) !== $this->callStartedAt) return;

        // Call next number
        $allNumbers = $state['all_numbers'];
        if ($this->currentIndex >= count($allNumbers)) {
            // All 75 numbers called with no winner — end game, refund pot equally
            $this->endGame($game, [], true);
            return;
        }

        $number = $allNumbers[$this->currentIndex];
        $state['called_numbers'][] = $number;
        $state['current_index']    = $this->currentIndex + 1;
        $state['call_started_at']  = time();

        // Check BINGO for each player
        $winnerIds = [];
        foreach ($state['players'] as &$player) {
            if ($player['is_winner']) continue;
            if ($this->checkBingo($player['card'], $state['called_numbers'])) {
                $player['is_winner'] = true;
                $winnerIds[] = $player['id'];
            }
        }
        unset($player);

        if (!empty($winnerIds)) {
            $state['winner_ids'] = $winnerIds;
            $state['phase']      = 'finished';
            $game->state = $state;
            $game->save();

            $this->endGame($game, $winnerIds, false);
            return;
        }

        $game->state = $state;
        $game->save();

        event(new BingoRoomUpdated($this->tableId, [
            'type'           => 'number_called',
            'number'         => $number,
            'called_numbers' => $state['called_numbers'],
            'current_index'  => $state['current_index'],
        ]));

        // Dispatch next call in 5 seconds
        self::dispatch(
            $this->tableId,
            $game->id,
            $state['current_index'],
            $state['call_started_at']
        )->delay(now()->addSeconds(5));
    }

    /**
     * Check whether a bingo card has at least one complete row, column, or diagonal.
     * card[row][col]; value 0 = FREE space (always marked).
     */
    private function checkBingo(array $card, array $calledNums): bool
    {
        $calledSet = array_flip($calledNums);

        // Build 5x5 marked grid
        $marked = [];
        for ($r = 0; $r < 5; $r++) {
            for ($c = 0; $c < 5; $c++) {
                $n = $card[$r][$c];
                $marked[$r][$c] = ($n === 0) || isset($calledSet[$n]);
            }
        }

        // Check rows
        for ($r = 0; $r < 5; $r++) {
            if ($marked[$r][0] && $marked[$r][1] && $marked[$r][2] && $marked[$r][3] && $marked[$r][4]) return true;
        }

        // Check columns
        for ($c = 0; $c < 5; $c++) {
            if ($marked[0][$c] && $marked[1][$c] && $marked[2][$c] && $marked[3][$c] && $marked[4][$c]) return true;
        }

        // Check main diagonal (top-left to bottom-right)
        if ($marked[0][0] && $marked[1][1] && $marked[2][2] && $marked[3][3] && $marked[4][4]) return true;

        // Check anti-diagonal (top-right to bottom-left)
        if ($marked[0][4] && $marked[1][3] && $marked[2][2] && $marked[3][1] && $marked[4][0]) return true;

        return false;
    }

    /**
     * Finalise the game: pay out winners or refund all players.
     */
    private function endGame(BingoGame $game, array $winnerIds, bool $refund): void
    {
        $state          = $game->state;
        $state['phase'] = 'finished';
        $game->state    = $state;
        $game->save();

        $table = BingoTable::find($this->tableId);
        $pot   = (int) $state['pot'];

        if ($refund || empty($winnerIds)) {
            // Refund entry fees equally to all seated players
            $humanIds = $table ? $table->players()->pluck('users.id')->toArray() : [];
            $share    = $humanIds ? (int) floor($pot / count($humanIds)) : 0;

            foreach ($humanIds as $uid) {
                if ($share > 0) {
                    $user = User::find($uid);
                    if ($user) {
                        $bal = $user->z_coins;
                        $user->increment('z_coins', $share);
                        ZooCoinTransaction::create([
                            'user_id'        => $uid,
                            'type'           => 'bingo_payout',
                            'amount'         => $share,
                            'balance_before' => $bal,
                            'balance_after'  => $bal + $share,
                            'note'           => 'Bingo hoàn tiền (không ai thắng)',
                        ]);
                    }
                }
            }
        } else {
            // Split pot equally among all winners
            $share = (int) floor($pot / count($winnerIds));

            foreach ($winnerIds as $uid) {
                $user = User::find($uid);
                if ($user && $share > 0) {
                    $bal = $user->z_coins;
                    $user->increment('z_coins', $share);
                    ZooCoinTransaction::create([
                        'user_id'        => $uid,
                        'type'           => 'bingo_payout',
                        'amount'         => $share,
                        'balance_before' => $bal,
                        'balance_after'  => $bal + $share,
                        'note'           => 'Bingo thắng',
                    ]);
                }
            }
        }

        // Reset ready flags for the next round
        if ($table) {
            \Illuminate\Support\Facades\DB::table('bingo_table_players')
                ->where('bingo_table_id', $table->id)
                ->update(['is_ready' => false]);
        }

        event(new BingoRoomUpdated($this->tableId, [
            'type'       => 'game_finished',
            'winner_ids' => $winnerIds,
            'pot'        => $pot,
            'refund'     => $refund,
        ]));
    }
}
