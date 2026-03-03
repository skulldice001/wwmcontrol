<?php

namespace App\Events;

use App\Models\PokerTable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PokerTableUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $table;

    /**
     * Create a new event instance.
     */
    public function __construct(PokerTable $table)
    {
        $this->table = $table;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('poker.lobby'),
        ];
    }
}
