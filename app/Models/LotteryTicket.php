<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotteryTicket extends Model
{
    protected $fillable = [
        'lottery_draw_id', 'user_id', 'picked_number',
        'bet_amount', 'is_winner', 'payout',
    ];

    protected $casts = [
        'is_winner' => 'boolean',
    ];

    public function draw(): BelongsTo
    {
        return $this->belongsTo(LotteryDraw::class, 'lottery_draw_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
