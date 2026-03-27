<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class PokerRoomUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public int   $tableId;
    public array $payload;

    public function __construct(int $tableId, array $payload)
    {
        $this->tableId = $tableId;
        $this->payload = $payload;
    }

    public function broadcastOn(): array
    {
        return [new Channel("poker.room.{$this->tableId}")];
    }

    /** Flatten payload to top-level broadcast data */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
