<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BingoRoomUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int   $tableId,
        public readonly array $payload,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("bingo.{$this->tableId}");
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
