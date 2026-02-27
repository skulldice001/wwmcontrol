<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'dark_mode',
        'navbar_variant',
        'sidebar_variant',
        'brand_logo_variant',
        'accent_color',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
