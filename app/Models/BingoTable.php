<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BingoTable extends Model
{
    protected $fillable = [
        'name', 'status', 'current_players', 'max_players', 'min_players', 'entry_fee',
    ];

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bingo_table_players')
            ->withPivot('joined_at', 'is_ready')
            ->withTimestamps();
    }

    public function games(): HasMany
    {
        return $this->hasMany(BingoGame::class);
    }
}
