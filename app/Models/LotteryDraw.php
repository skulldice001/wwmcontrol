<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotteryDraw extends Model
{
    protected $fillable = [
        'type', 'status', 'draw_at', 'drawn_at',
        'winning_numbers', 'pick_count', 'multiplier',
        'total_tickets', 'total_pot', 'total_payout',
    ];

    protected $casts = [
        'draw_at'         => 'datetime',
        'drawn_at'        => 'datetime',
        'winning_numbers' => 'array',
    ];

    const NUMBER_MIN = 1;
    const NUMBER_MAX = 99;

    const DAILY_MULTIPLIER  = 10;
    const WEEKLY_MULTIPLIER = 70;
    const DAILY_PICK_COUNT  = 2;
    const WEEKLY_PICK_COUNT = 1;

    public function tickets(): HasMany
    {
        return $this->hasMany(LotteryTicket::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open' && now()->lt($this->draw_at);
    }

    /** Return current open draw for type, creating one if none exists.
     *  Only considers draws whose draw_at is still in the future (open for ticket sales).
     */
    public static function getOrCreateOpen(string $type): self
    {
        // Find an open draw that hasn't reached its draw time yet
        $draw = self::where('type', $type)
            ->where('status', 'open')
            ->where('draw_at', '>', now())
            ->latest('draw_at')
            ->first();

        if ($draw) return $draw;

        // Avoid duplicate: if a draw for the next scheduled time already exists, return it
        $nextAt = self::nextDrawAt($type);
        $existing = self::where('type', $type)
            ->where('draw_at', $nextAt)
            ->first();

        if ($existing) {
            // Re-open if somehow closed without being drawn
            if ($existing->status !== 'open') {
                $existing->update(['status' => 'open']);
            }
            return $existing;
        }

        return self::createNext($type);
    }

    /** Create next draw record for the given type. */
    public static function createNext(string $type): self
    {
        $drawAt     = self::nextDrawAt($type);
        $multiplier = $type === 'weekly' ? self::WEEKLY_MULTIPLIER : self::DAILY_MULTIPLIER;
        $pickCount  = $type === 'weekly' ? self::WEEKLY_PICK_COUNT : self::DAILY_PICK_COUNT;

        return self::create([
            'type'       => $type,
            'status'     => 'open',
            'draw_at'    => $drawAt,
            'pick_count' => $pickCount,
            'multiplier' => $multiplier,
        ]);
    }

    /** Calculate next draw_at for the given type. */
    public static function nextDrawAt(string $type): Carbon
    {
        if ($type === 'weekly') {
            // Next Saturday at 21:00
            $next = now()->next('Saturday')->setTime(21, 0, 0);
            // If today IS Saturday and time < 21:00, use today
            if (now()->isSaturday() && now()->lt(now()->copy()->setTime(21, 0, 0))) {
                $next = now()->copy()->setTime(21, 0, 0);
            }
            return $next;
        }

        // Daily: next 20:00
        $today8pm = now()->copy()->setTime(20, 0, 0);
        return now()->lt($today8pm) ? $today8pm : $today8pm->addDay();
    }

    /** Seconds until draw. */
    public function secondsUntilDraw(): int
    {
        return max(0, (int) now()->diffInSeconds($this->draw_at, false));
    }
}
