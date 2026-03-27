<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaixiuMessage extends Model
{
    protected $fillable = ['table_id', 'user_id', 'message'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function table()
    {
        return $this->belongsTo(TaixiuTable::class);
    }
}
