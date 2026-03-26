<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BlackjackRoomUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int     $tableId,
        public string  $type,        // 'ready_update' | 'countdown_start' | 'countdown_cancel'
        public array   $players,
        public ?int    $countdownAt = null,
    ) {}

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
        ];
    }
}
