<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TienLenGame extends Model
{
    protected $fillable = ['tienlen_table_id', 'state', 'status'];

    protected $casts = [
        'state' => 'array',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(TienLenTable::class, 'tienlen_table_id');
    }
}
