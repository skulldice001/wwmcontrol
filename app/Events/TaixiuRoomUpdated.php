<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TaixiuRoomUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int    $tableId,
        public array  $payload,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel("taixiu.room.{$this->tableId}")];
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
