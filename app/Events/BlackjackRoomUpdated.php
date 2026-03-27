<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class BlackjackRoomUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public int    $tableId;
    public string $type;
    public array  $players;
    public ?int   $countdownAt;
    public array  $roundState;

    public function __construct(
        int    $tableId,
        string $type,
        array  $players      = [],
        ?int   $countdownAt  = null,
        array  $roundState   = []
    ) {
        $this->tableId     = $tableId;
        $this->type        = $type;
        $this->players     = $players;
        $this->countdownAt = $countdownAt;
        $this->roundState  = $roundState;
    }

    public function broadcastOn(): array
    {
        return [new Channel("blackjack.room.{$this->tableId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'type'         => $this->type,
            'players'      => $this->players,
            'countdown_at' => $this->countdownAt,
            'round_state'  => $this->roundState,
        ];
    }
}
