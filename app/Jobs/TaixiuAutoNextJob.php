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

class TaixiuAutoNextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $tableId,
        private int $gameId,
        private int $resultDeadline, // stale guard
    ) {}

    public function handle(): void
    {
        $game = TaixiuGame::find($this->gameId);
        if (!$game) return;

        $state = $game->state;
        if ($state['phase'] !== 'result') return;
        if (($state['result_deadline_at'] ?? 0) !== $this->resultDeadline) return;

        // Mark finished
        $game->update(['state' => array_merge($state, ['phase' => 'finished'])]);

        $table = TaixiuTable::find($this->tableId);
        if (!$table || $table->players()->count() === 0) return;

        $newGame = TaixiuEngine::startRound($table);
        $newState = TaixiuEngine::clientState($newGame);

        event(new TaixiuRoomUpdated($this->tableId, [
            'type'  => 'round_started',
            'state' => $newState,
        ]));

        TaixiuAutoRollJob::dispatch(
            $this->tableId,
            $newGame->id,
            $newGame->state['bet_deadline_at']
        )->delay(now()->addSeconds(TaixiuEngine::BETTING_DURATION));
    }
}
