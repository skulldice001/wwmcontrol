<?php

namespace App\Jobs;

use App\Events\TienLenTableUpdated;
use App\Models\TienLenGame;
use App\Services\TienLen\GameEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TienLenAutoPassJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $gameId,
        public int $userId,
    ) {}

    public function handle(): void
    {
        $game = TienLenGame::find($this->gameId);
        if (!$game || $game->status !== 'active') return;

        $state = $game->state;
        $current = $state['current_player'];

        // Only auto-pass if it's still this user's turn
        if ($state['players'][$current]['user_id'] !== $this->userId) return;

        // If it's the first turn, auto-play 3♠
        if ($state['first_turn']) {
            $result = GameEngine::playCards($game, $this->userId, [0]);
        } else {
            // Auto pass
            $result = GameEngine::playCards($game, $this->userId, []);
        }

        if (isset($result['ok'])) {
            broadcast(new TienLenTableUpdated($game->table->load('players')));
        }
    }
}
