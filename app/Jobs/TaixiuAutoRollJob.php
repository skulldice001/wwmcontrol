<?php

namespace App\Jobs;

use App\Events\TaixiuRoomUpdated;
use App\Models\TaixiuGame;
use App\Models\TaixiuTable;
use App\Services\Taixiu\TaixiuEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TaixiuAutoRollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $tableId,
        private int $gameId,
        private int $betDeadline, // stale guard
    ) {}

    public function handle(): void
    {
        $game = TaixiuGame::find($this->gameId);
        if (!$game) return;

        $state = $game->state;
        if ($state['phase'] !== 'betting') return;
        if (($state['bet_deadline_at'] ?? 0) !== $this->betDeadline) return;

        $game = TaixiuEngine::roll($game);

        $table      = TaixiuTable::find($this->tableId);
        $playerIds  = $table?->players()->pluck('users.id')->toArray() ?? [];

        $state = TaixiuEngine::clientState($game);
        event(new TaixiuRoomUpdated($this->tableId, [
            'type'  => 'dice_rolled',
            'state' => $state,
        ]));

        // Schedule next round
        TaixiuAutoNextJob::dispatch(
            $this->tableId,
            $game->id,
            $game->state['result_deadline_at'] ?? (time() + TaixiuEngine::RESULT_DURATION)
        )->delay(now()->addSeconds(TaixiuEngine::RESULT_DURATION));
    }
}
