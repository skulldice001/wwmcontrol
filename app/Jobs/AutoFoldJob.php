<?php

namespace App\Jobs;

use App\Events\PokerRoomUpdated;
use App\Models\PokerGame;
use App\Models\PokerTable;
use App\Models\User;
use App\Services\Poker\GameEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoFoldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $tableId,
        private int $gameId,
        private int $playerIdx,
        private int $turnStartedAt,
    ) {}

    public function handle(): void
    {
        $game = PokerGame::find($this->gameId);
        if (!$game) return;

        $state = $game->state;

        // Guard: only act if it's still the same player's turn in the same game snapshot
        if ($state['phase'] === 'showdown') return;
        if ($state['current_player'] !== $this->playerIdx) return;
        if (($state['turn_started_at'] ?? 0) !== $this->turnStartedAt) return;

        $p = $state['players'][$this->playerIdx];
        if ($p['is_ai'] || $p['status'] !== 'active' || !$p['pending']) return;

        // Auto-fold the timed-out player
        $game = GameEngine::processAction($game, 'fold', 0, (int) $p['id']);

        // Settle Z-Coins if the hand reached showdown
        if ($game->state['phase'] === 'showdown') {
            $table    = PokerTable::find($this->tableId);
            $humanIds = $table?->players()->pluck('users.id')->toArray() ?? [];
            foreach ($game->state['players'] as $player) {
                if ($player['is_ai'] || !in_array($player['id'], $humanIds)) continue;
                $finalChips = (int) $player['chips'];
                if ($finalChips > 0) {
                    User::where('id', $player['id'])->increment('z_coins', $finalChips);
                }
            }
        }

        // Tell all clients to refresh their state
        event(new PokerRoomUpdated($this->tableId, [
            'type'    => 'refresh',
            'game_id' => $game->id,
        ]));

        // Re-dispatch for the next human player's turn
        $newState = $game->state;
        if ($newState['phase'] !== 'showdown') {
            $nextP = $newState['players'][$newState['current_player']];
            if (!$nextP['is_ai'] && $nextP['status'] === 'active' && $nextP['pending']) {
                self::dispatch(
                    $this->tableId,
                    $game->id,
                    $newState['current_player'],
                    $newState['turn_started_at']
                )->delay(now()->addSeconds(60));
            }
        }
    }
}
