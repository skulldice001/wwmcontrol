<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TienLenTablePlayer extends Model
{
    public $timestamps = false;

    protected $table = 'tienlen_table_players';

    protected $fillable = [
        'tienlen_table_id', 'user_id', 'seat', 'is_ready', 'joined_at',
    ];

    protected $casts = [
        'is_ready'  => 'boolean',
        'joined_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(TienLenTable::class, 'tienlen_table_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
