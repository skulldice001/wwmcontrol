<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlackjackGame extends Model
{
    protected $guarded = [];
    protected $casts   = ['state' => 'array'];

    public function blackjackTable()
    {
        return $this->belongsTo(BlackjackTable::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
