<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
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
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'formation_data' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class)->withPivot('preferred_time')->withTimestamps();
    }
}
