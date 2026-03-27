<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $attributes = [
        'z_coins'        => 5000,
        'z_coins_frozen' => 0,
    ];

    protected $fillable = [
        'name',
        'account',
        'email',
        'password',
        'discord_id',
        'discord_token',
        'discord_refresh_token',
        'discord_avatar',
        'avatar',
        'country',
        'online_from',
        'online_to',
        'ingame_name',
        'ingame_id',
        'main_skill_id',
        'sub_skill_id',
        'z_coins',
        'z_coins_frozen',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'z_coins'          => 'integer',
            'z_coins_frozen'   => 'integer',
        ];
    }

    public function events()
    {
        return $this->belongsToMany(Event::class)->withPivot('preferred_time')->withTimestamps();
    }

    public function innerWays()
    {
        return $this->belongsToMany(InnerWay::class, 'user_inner_way')->withPivot('level')->withTimestamps();
    }

    public function mainSkill()
    {
        return $this->belongsTo(Skill::class, 'main_skill_id');
    }

    public function subSkill()
    {
        return $this->belongsTo(Skill::class, 'sub_skill_id');
    }

    public function themeSetting()
    {
        return $this->hasOne(ThemeSetting::class);
    }

    public function zCoinTransactions()
    {
        return $this->hasMany(ZooCoinTransaction::class);
    }

    public function availableZCoins(): int
    {
        return max(0, (int) $this->z_coins - (int) $this->z_coins_frozen);
    }
}
