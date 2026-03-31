<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TienLenTable extends Model
{
    protected $table = 'tienlen_tables';

    protected $fillable = [
        'owner_id', 'name', 'variant', 'entry_fee', 'status', 'is_ai_mode',
    ];

    protected $casts = [
        'is_ai_mode' => 'boolean',
        'entry_fee'  => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tienlen_table_players', 'tienlen_table_id', 'user_id')
            ->withPivot('seat', 'is_ready', 'joined_at');
    }

    public function tablePlayerRecords(): HasMany
    {
        return $this->hasMany(TienLenTablePlayer::class, 'tienlen_table_id');
    }

    public function games(): HasMany
    {
        return $this->hasMany(TienLenGame::class, 'tienlen_table_id');
    }

    public function activeGame(): HasOne
    {
        return $this->hasOne(TienLenGame::class, 'tienlen_table_id')
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TienLenMessage::class, 'tienlen_table_id');
    }
}
