<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ZooDailyBonus extends Command
{
    protected $signature   = 'zoo:daily-bonus';
    protected $description = 'Give 10,000 Zoo-coins to all active users daily';

    public function handle(): int
    {
        $bonus = 10000;
        $users = User::whereNull('deleted_at')->get();
        $count = 0;

        DB::transaction(function () use ($users, $bonus, &$count) {
            foreach ($users as $user) {
                $before = (int) $user->z_coins;
                $after  = $before + $bonus;

                $user->update(['z_coins' => $after]);

                ZooCoinTransaction::create([
                    'user_id'        => $user->id,
                    'type'           => 'daily_bonus',
                    'amount'         => $bonus,
                    'balance_before' => $before,
                    'balance_after'  => $after,
                    'note'           => 'Thưởng Zoo hàng ngày',
                ]);

                $count++;
            }
        });

        $this->info("Đã tặng {$bonus} Zoo-coin cho {$count} người dùng.");
        return self::SUCCESS;
    }
}
