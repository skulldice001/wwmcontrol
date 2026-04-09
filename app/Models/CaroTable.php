<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CaroTable extends Model
{
    protected $fillable = [
        'name', 'entry_fee', 'player_x_id', 'player_o_id', 'status',
    ];

    protected $casts = [
        'entry_fee' => 'integer',
    ];

    public function playerX(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_x_id');
    }

    public function playerO(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_o_id');
    }

    public function games(): HasMany
    {
        return $this->hasMany(CaroGame::class);
    }

    public function activeGame(): HasOne
    {
        return $this->hasOne(CaroGame::class)->where('status', 'playing')->latestOfMany();
    }

    public function playerCount(): int
    {
        return ($this->player_x_id ? 1 : 0) + ($this->player_o_id ? 1 : 0);
    }
}
