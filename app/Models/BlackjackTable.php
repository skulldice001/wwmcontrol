<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlackjackTable extends Model
{
    protected $guarded = [];

    public function players()
    {
        return $this->belongsToMany(User::class, 'blackjack_table_players')
                    ->withPivot('joined_at', 'is_ready', 'role', 'seat')
                    ->orderByPivot('seat');
    }

    public function rounds()
    {
        return $this->hasMany(BlackjackRound::class);
    }

    public function activeRound(): ?BlackjackRound
    {
        return $this->rounds()->whereNotIn('phase', ['finished'])->latest()->first();
    }

    public function games()
    {
        return $this->hasMany(BlackjackGame::class);
    }
}
