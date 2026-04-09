<?php

namespace App\Events;

use App\Models\CaroTable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaroTableUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public CaroTable $table) {}

    public function broadcastOn(): array
    {
        return [new Channel('caro-lobby')];
    }

    public function broadcastAs(): string
    {
        return 'table.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'             => $this->table->id,
            'name'           => $this->table->name,
            'board_size'     => $this->table->board_size,
            'entry_fee'      => $this->table->entry_fee,
            'status'         => $this->table->status,
            'player_count'   => $this->table->playerCount(),
            'player_x_name'  => $this->table->playerX?->name,
            'player_o_name'  => $this->table->playerO?->name,
        ];
    }
}
