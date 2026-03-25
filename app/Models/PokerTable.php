<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PokerTable extends Model
{
    protected $guarded = [];

    /** Users currently sitting at this table */
    public function players()
    {
        return $this->belongsToMany(User::class, 'poker_table_players')
                    ->withPivot('joined_at', 'is_ready');
    }
}
