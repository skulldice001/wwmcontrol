<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Lottery draws ─────────────────────────────────────────────────────────
// Daily: draw at 20:00, ticket sales close at 19:00 (1 hour before)
Schedule::command('lottery:draw daily')->dailyAt('20:00');

// Jackpot: draw at 20:30 every day, pot carries over if no winner
Schedule::command('lottery:draw jackpot')->dailyAt('20:30');

// Weekly: draw at 21:00 every Saturday
Schedule::command('lottery:draw weekly')->weeklyOn(6, '21:00');

// ── Daily bonus ──────────────────────────────────────────────────────────
// Grant Zoo Coins to all active users at the start of each day
Schedule::command('zoo:daily-bonus')->dailyAt('00:05');

// ── Events ───────────────────────────────────────────────────────────────
// Close expired events every hour
Schedule::command('events:close-expired')->hourly();
