<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaroGame extends Model
{
    protected $fillable = [
        'caro_table_id', 'moves', 'min_row', 'max_row', 'min_col', 'max_col',
        'current_player', 'moves_count', 'winner', 'winning_cells',
        'turn_deadline', 'status',
    ];

    protected $casts = [
        'moves'         => 'array',
        'winning_cells' => 'array',
        'turn_deadline' => 'datetime',
        'min_row'       => 'integer',
        'max_row'       => 'integer',
        'min_col'       => 'integer',
        'max_col'       => 'integer',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(CaroTable::class, 'caro_table_id');
    }

    /** Build a lookup dict from moves: "row,col" => "X"|"O" */
    public function boardDict(): array
    {
        $dict = [];
        foreach ($this->moves ?? [] as $m) {
            $dict["{$m[0]},{$m[1]}"] = $m[2];
        }
        return $dict;
    }
}
