<?php

namespace App\Events;

use App\Models\BlackjackTable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BlackjackTableUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BlackjackTable $table) {}

    public function broadcastOn(): array
    {
        return [new Channel('blackjack.lobby')];
    }

    public function broadcastWith(): array
    {
        return ['table' => $this->table->toArray()];
    }
}
