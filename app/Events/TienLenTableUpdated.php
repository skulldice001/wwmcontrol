<?php

namespace App\Events;

use App\Models\TienLenTable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TienLenTableUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TienLenTable $table)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('tienlen.' . $this->table->id)];
    }

    public function broadcastAs(): string
    {
        return 'table.updated';
    }

    public function broadcastWith(): array
    {
        return ['table_id' => $this->table->id];
    }
}
