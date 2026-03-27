<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZooCoinTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'note',
        'staff_id',
        'related_user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function relatedUser()
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'add'               => 'Nạp',
            'deduct'            => 'Trừ',
            'transfer_in'       => 'Nhận',
            'transfer_out'      => 'Chuyển',
            'daily_bonus'       => 'Thưởng ngày',
            'blackjack_bet'     => 'Blackjack cược',
            'blackjack_payout'  => 'Blackjack thắng',
            'poker_bet'         => 'Poker buy-in',
            'poker_payout'      => 'Poker thắng',
            'taixiu_bet'        => 'Tài Xỉu cược',
            'taixiu_payout'     => 'Tài Xỉu thắng',
            default             => $this->type,
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'add', 'transfer_in', 'daily_bonus', 'blackjack_payout', 'poker_payout', 'taixiu_payout' => 'badge-success',
            'deduct', 'transfer_out', 'blackjack_bet', 'poker_bet', 'taixiu_bet'                    => 'badge-danger',
            default                                                  => 'badge-secondary',
        };
    }
}
