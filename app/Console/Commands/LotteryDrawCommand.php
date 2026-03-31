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
    protected $signature   = 'lottery:draw {type : daily, weekly, or jackpot}';
    protected $description = 'Conduct the lottery draw and settle payouts';

    public function handle(): int
    {
        $type = $this->argument('type');

        if (!in_array($type, ['daily', 'weekly', 'jackpot'])) {
            $this->error('Type must be daily, weekly, or jackpot.');
            return self::FAILURE;
        }

        if ($type === 'jackpot') {
            return $this->handleJackpot();
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

    private function handleJackpot(): int
    {
        $draw = LotteryDraw::where('type', 'jackpot')
            ->where('status', 'open')
            ->where('draw_at', '<=', now())
            ->latest('draw_at')
            ->first();

        if (!$draw) {
            $this->info("No pending jackpot draw found.");
            LotteryDraw::getOrCreateJackpot();
            return self::SUCCESS;
        }

        $this->info("Conducting jackpot draw #{$draw->id} scheduled at {$draw->draw_at}...");

        // Draw 1 number from 00–99
        $winning = rand(LotteryDraw::JACKPOT_NUMBER_MIN, LotteryDraw::JACKPOT_NUMBER_MAX);

        $draw->update([
            'status'          => 'drawn',
            'winning_numbers' => [$winning],
            'drawn_at'        => now(),
        ]);

        $this->line("Winning number: " . str_pad($winning, 2, '0', STR_PAD_LEFT));

        $totalPayout  = 0;
        $carryoverPot = 0;

        DB::transaction(function () use ($draw, $winning, &$totalPayout, &$carryoverPot) {
            $tickets = LotteryTicket::where('lottery_draw_id', $draw->id)
                ->with('user')
                ->lockForUpdate()
                ->get();

            $winningTickets = $tickets->filter(fn($t) => (int) $t->picked_number === $winning);
            $winnerCount    = $winningTickets->count();

            if ($winnerCount > 0) {
                $potPerWinner = (int) floor($draw->total_pot / $winnerCount);

                foreach ($winningTickets as $ticket) {
                    $ticket->update(['is_winner' => true, 'payout' => $potPerWinner]);

                    $user      = $ticket->user;
                    $balBefore = $user->z_coins;
                    $user->increment('z_coins', $potPerWinner);

                    ZooCoinTransaction::create([
                        'user_id'        => $user->id,
                        'type'           => 'lottery_payout',
                        'amount'         => $potPerWinner,
                        'balance_before' => $balBefore,
                        'balance_after'  => $balBefore + $potPerWinner,
                        'note'           => 'Trúng Jackpot! Số ' . str_pad($winning, 2, '0', STR_PAD_LEFT),
                    ]);

                    $totalPayout += $potPerWinner;
                }

                foreach ($tickets->filter(fn($t) => (int) $t->picked_number !== $winning) as $ticket) {
                    $ticket->update(['is_winner' => false, 'payout' => 0]);
                }

                $draw->update(['status' => 'settled', 'total_payout' => $totalPayout]);
                $carryoverPot = 0;
            } else {
                foreach ($tickets as $ticket) {
                    $ticket->update(['is_winner' => false, 'payout' => 0]);
                }
                $draw->update(['status' => 'settled', 'total_payout' => 0]);
                $carryoverPot = $draw->total_pot;
            }
        });

        if ($carryoverPot > 0) {
            $this->info("No winners. Pot of {$carryoverPot} Zoo carries over to next draw.");
        } else {
            $this->info("Winner(s) paid! Total payout: {$totalPayout} Zoo.");
        }

        $next = LotteryDraw::getOrCreateJackpot($carryoverPot);
        $this->info("Next jackpot draw created: #{$next->id} at {$next->draw_at}");

        return self::SUCCESS;
    }
}
