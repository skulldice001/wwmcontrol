<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaixiuGame extends Model
{
    protected $guarded = [];

    protected $casts = [
        'state' => 'array',
    ];

    public function table()
    {
        return $this->belongsTo(TaixiuTable::class, 'taixiu_table_id');
    }
}
