<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\ZooCoinTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RunLuckyDraws extends Command
{
    protected $signature   = 'events:run-lucky-draws';
    protected $description = 'Execute all pending lucky draw events whose draw_at time has passed';

    public function handle(): int
    {
        $pending = Event::where('type', 'lucky_draw')
            ->where('status', 'upcoming')
            ->get()
            ->filter(fn ($e) => $e->luckyDrawPending());

        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($pending as $event) {
            $this->runDraw($event);
        }

        return self::SUCCESS;
    }

    private function runDraw(Event $event): void
    {
        $data         = $event->lucky_draw_data;
        $prizes       = $data['prizes'] ?? [];
        $participants = $event->participants()->get();

        if ($participants->isEmpty() || empty($prizes)) {
            // No participants — mark completed with no winners
            $data['drawn_at'] = now()->toDateTimeString();
            $data['winners']  = [];
            $event->update(['lucky_draw_data' => $data, 'status' => 'completed', 'end_time' => now()]);
            $this->warn("  [{$event->id}] {$event->title} — no participants, skipped draw.");
            return;
        }

        $pool    = $participants->shuffle();
        $winners = [];

        foreach ($prizes as $index => $prize) {
            if ($pool->isEmpty()) break;

            $winner = $pool->shift();
            $amount = (int) ($prize['zoo_coin_amount'] ?? 0);

            if ($amount > 0) {
                $winner->increment('z_coins', $amount);
                ZooCoinTransaction::create([
                    'user_id'     => $winner->id,
                    'amount'      => $amount,
                    'type'        => 'earn',
                    'description' => "Trúng thưởng {$prize['name']} — {$event->title}",
                ]);
            }

            $winners[] = [
                'rank'            => $index + 1,
                'prize_name'      => $prize['name'],
                'prize_desc'      => $prize['description'] ?? '',
                'zoo_coin_amount' => $amount,
                'user_id'         => $winner->id,
                'username'        => $winner->name,
                'ingame_name'     => $winner->ingame_name ?? $winner->name,
            ];
        }

        $data['drawn_at'] = now()->toDateTimeString();
        $data['winners']  = $winners;

        $event->update(['lucky_draw_data' => $data, 'status' => 'completed', 'end_time' => now()]);

        $this->info("  [{$event->id}] {$event->title} — drew " . count($winners) . " winner(s).");
    }
}
