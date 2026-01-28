<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InnerWay extends Model
{
    protected $fillable = ['name', 'slug', 'icon', 'color'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_inner_way')->withPivot('level')->withTimestamps();
    }
}
