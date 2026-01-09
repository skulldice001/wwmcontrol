<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
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
    ];

    public function creator()
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class);
    }
}
