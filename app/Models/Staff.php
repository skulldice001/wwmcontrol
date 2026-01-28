<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Staff extends Authenticatable
{
    use Notifiable, HasFactory;

    protected $table = 'staffs';

    protected $fillable = [
        'name',
        'account',
        'email',
        'password',
        'role',
    ];

    const ROLE_MASTER = 'master';
    const ROLE_ADMIN = 'admin';
    const ROLE_OBSERVER = 'observer';

    public function isMaster(): bool
    {
        return $this->role === self::ROLE_MASTER;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_MASTER, self::ROLE_ADMIN]);
    }

    public function isObserver(): bool
    {
        return $this->role === self::ROLE_OBSERVER;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
