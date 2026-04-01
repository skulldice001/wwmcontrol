<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Staff extends Authenticatable
{
    use Notifiable, HasFactory, SoftDeletes;

    protected $table = 'staffs';

    protected $fillable = [
        'name',
        'account',
        'email',
        'password',
        'role',
    ];

    const ROLE_MASTER    = 'master';
    const ROLE_ADMIN     = 'admin';
    const ROLE_OBSERVER  = 'observer';
    const ROLE_LIBRARIAN = 'librarian';

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

    public function isLibrarian(): bool
    {
        return $this->role === self::ROLE_LIBRARIAN;
    }

    public function canManageLibrary(): bool
    {
        return in_array($this->role, [self::ROLE_MASTER, self::ROLE_ADMIN, self::ROLE_LIBRARIAN]);
    }

    public function roleRank(): int
    {
        return match ($this->role) {
            self::ROLE_MASTER    => 3,
            self::ROLE_ADMIN     => 2,
            self::ROLE_OBSERVER  => 1,
            self::ROLE_LIBRARIAN => 1,
            default              => 0,
        };
    }

    public function canManage(Staff $other): bool
    {
        if ($this->id === $other->id) {
            return true;
        }

        return $this->roleRank() > $other->roleRank();
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
