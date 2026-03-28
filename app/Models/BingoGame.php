<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BingoGame extends Model
{
    protected $fillable = ['bingo_table_id', 'state'];

    protected $casts = ['state' => 'array'];

    public function table(): BelongsTo
    {
        return $this->belongsTo(BingoTable::class, 'bingo_table_id');
    }
}
