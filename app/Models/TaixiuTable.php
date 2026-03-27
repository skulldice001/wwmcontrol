<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaixiuTable extends Model
{
    protected $guarded = [];

    public function players()
    {
        return $this->belongsToMany(User::class, 'taixiu_table_players')
                    ->withPivot('joined_at');
    }

    public function games()
    {
        return $this->hasMany(TaixiuGame::class, 'taixiu_table_id');
    }

    public function activeGame(): ?TaixiuGame
    {
        return $this->games()
            ->whereJsonNotContains('state->phase', 'finished')
            ->latest()
            ->first();
    }
}
