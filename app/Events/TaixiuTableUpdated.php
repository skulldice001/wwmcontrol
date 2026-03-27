<?php

namespace App\Events;

use App\Models\TaixiuTable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaixiuTableUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TaixiuTable $table) {}

    public function broadcastOn(): array
    {
        return [new Channel('taixiu.lobby')];
    }

    public function broadcastWith(): array
    {
        return ['table' => $this->table->toArray()];
    }
}
