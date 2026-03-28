<?php

namespace App\Console\Commands;

use App\Models\LotteryDraw;
use App\Models\LotteryTicket;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LotteryDrawCommand extends Command
{
    protected $signature   = 'lottery:draw {type : daily or weekly}';
    protected $description = 'Conduct the lottery draw and settle payouts';

    public function handle(): int
    {
        $type = $this->argument('type');

        if (!in_array($type, ['daily', 'weekly'])) {
            $this->error('Type must be daily or weekly.');
            return self::FAILURE;
        }

        // Find open draw that is due
        $draw = LotteryDraw::where('type', $type)
            ->where('status', 'open')
            ->where('draw_at', '<=', now())
            ->latest('draw_at')
            ->first();

        if (!$draw) {
            $this->info("No pending {$type} draw found. Creating next draw.");
            LotteryDraw::createNext($type);
            return self::SUCCESS;
        }

        $this->info("Conducting {$type} draw #{$draw->id} scheduled at {$draw->draw_at}...");

        // Draw winning numbers
        $pool    = range(LotteryDraw::NUMBER_MIN, LotteryDraw::NUMBER_MAX);
        shuffle($pool);
        $winners = array_slice($pool, 0, $draw->pick_count);
        sort($winners);

        $draw->update([
            'status'          => 'drawn',
            'winning_numbers' => $winners,
            'drawn_at'        => now(),
        ]);

        $this->line("Winning numbers: " . implode(', ', $winners));

        // Settle payouts
        $totalPayout = 0;

        DB::transaction(function () use ($draw, $winners, &$totalPayout) {
            $tickets = LotteryTicket::where('lottery_draw_id', $draw->id)
                ->with('user')
                ->lockForUpdate()
                ->get();

            foreach ($tickets as $ticket) {
                $isWinner = in_array($ticket->picked_number, $winners);
                $payout   = $isWinner ? (int) ($ticket->bet_amount * $draw->multiplier) : 0;

                $ticket->update([
                    'is_winner' => $isWinner,
                    'payout'    => $payout,
                ]);

                if ($isWinner && $payout > 0) {
                    $user      = $ticket->user;
                    $balBefore = $user->z_coins;
                    $user->increment('z_coins', $payout);

                    ZooCoinTransaction::create([
                        'user_id'        => $user->id,
                        'type'           => 'lottery_payout',
                        'amount'         => $payout,
                        'balance_before' => $balBefore,
                        'balance_after'  => $balBefore + $payout,
                        'note'           => 'Trúng xổ số ' . ($draw->type === 'weekly' ? 'tuần' : 'ngày')
                                          . ' số ' . $ticket->picked_number,
                    ]);

                    $totalPayout += $payout;
                }
            }

            $draw->update([
                'status'        => 'settled',
                'total_payout'  => $totalPayout,
            ]);
        });

        $this->info("Settled. Total payout: {$totalPayout} Zoo to winners.");

        // Create next draw
        $next = LotteryDraw::createNext($type);
        $this->info("Next {$type} draw created: #{$next->id} at {$next->draw_at}");

        return self::SUCCESS;
    }
}
