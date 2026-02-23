<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use Carbon\Carbon;

class CloseExpiredEvents extends Command
{
    protected $signature = 'events:close-expired';

    protected $description = 'Mark expired events as completed';

    public function handle(): int
    {
        $now = Carbon::now();

        $affected = Event::whereIn('status', ['upcoming', 'ongoing'])
            ->where(function ($query) use ($now) {
                $query->whereNotNull('end_time')->where('end_time', '<', $now)
                    ->orWhere(function ($q) use ($now) {
                        $q->whereNull('end_time')->where('start_time', '<', $now);
                    });
            })
            ->update(['status' => 'completed']);

        $this->info("Closed {$affected} events.");

        return Command::SUCCESS;
    }
}

