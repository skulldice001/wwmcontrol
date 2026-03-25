<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PokerGame extends Model
{
    protected $guarded = [];

    protected $casts = [
        'state' => 'array',
    ];

    public function pokerTable()
    {
        return $this->belongsTo(PokerTable::class, 'poker_table_id');
    }
}
