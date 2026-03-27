<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlackjackRound extends Model
{
    protected $guarded = [];

    protected $casts = [
        'state' => 'array',
    ];

    public function table()
    {
        return $this->belongsTo(BlackjackTable::class, 'blackjack_table_id');
    }
}
