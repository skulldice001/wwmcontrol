<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlackjackTable extends Model
{
    protected $guarded = [];

    public function games()
    {
        return $this->hasMany(BlackjackGame::class);
    }
}
