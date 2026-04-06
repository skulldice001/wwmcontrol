<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    const TYPE_CASUAL     = 'casual';
    const TYPE_GUILD_WAR  = 'guild_war';
    const TYPE_LUCKY_DRAW = 'lucky_draw';

    protected $fillable = [
        'discord_id',
        'title',
        'description',
        'type',
        'rules',
        'rewards',
        'start_time',
        'end_time',
        'location',
        'status',
        'created_by',
        'formation_data',
        'lucky_draw_data',
    ];

    protected $casts = [
        'start_time'      => 'datetime',
        'end_time'        => 'datetime',
        'formation_data'  => 'array',
        'lucky_draw_data' => 'array',
    ];

    public function isLuckyDraw(): bool
    {
        return $this->type === self::TYPE_LUCKY_DRAW;
    }

    public function luckyDrawPending(): bool
    {
        $data = $this->lucky_draw_data;
        return $this->isLuckyDraw()
            && !empty($data['draw_at'])
            && empty($data['drawn_at'])
            && now()->gte(\Carbon\Carbon::parse($data['draw_at']));
    }

    public function luckyDrawDrawn(): bool
    {
        $data = $this->lucky_draw_data;
        return $this->isLuckyDraw() && !empty($data['drawn_at']);
    }

    public function creator()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class)->withPivot('preferred_time')->withTimestamps();
    }
}
