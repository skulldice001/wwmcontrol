<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotteryDraw extends Model
{
    protected $fillable = [
        'type', 'status', 'draw_at', 'opens_at', 'drawn_at',
        'winning_numbers', 'pick_count', 'multiplier',
        'total_tickets', 'total_pot', 'total_payout',
    ];

    protected $casts = [
        'draw_at'         => 'datetime',
        'opens_at'        => 'datetime',
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
        if ($this->status !== 'open') return false;
        if ($this->opens_at && now()->lt($this->opens_at)) return false;
        // Daily: ticket sales close 1 hour before draw (07:00); Weekly: close at draw time
        $cutoff = $this->type === 'daily'
            ? $this->draw_at->copy()->subHour()
            : $this->draw_at;
        if (now()->gte($cutoff)) return false;
        return true;
    }

    /** Seconds until ticket sales open (0 if already open). */
    public function secondsUntilOpen(): int
    {
        if (!$this->opens_at || now()->gte($this->opens_at)) return 0;
        return max(0, (int) now()->diffInSeconds($this->opens_at, false));
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
        $opensAt    = self::nextOpensAt($type, $drawAt);
        $multiplier = $type === 'weekly' ? self::WEEKLY_MULTIPLIER : self::DAILY_MULTIPLIER;
        $pickCount  = $type === 'weekly' ? self::WEEKLY_PICK_COUNT : self::DAILY_PICK_COUNT;

        return self::create([
            'type'       => $type,
            'status'     => 'open',
            'draw_at'    => $drawAt,
            'opens_at'   => $opensAt,
            'pick_count' => $pickCount,
            'multiplier' => $multiplier,
        ]);
    }

    /** Calculate next draw_at for the given type. */
    public static function nextDrawAt(string $type): Carbon
    {
        if ($type === 'weekly') {
            // Next Saturday at 21:00
            $saturday21 = now()->copy()->setTime(21, 0, 0);
            if (!now()->isSaturday() || now()->gte($saturday21)) {
                $saturday21 = now()->next('Saturday')->setTime(21, 0, 0);
            }
            return $saturday21;
        }

        // Daily: 08:00 today, or 08:00 tomorrow if already past
        $today08 = now()->copy()->setTime(8, 0, 0);
        return now()->lt($today08) ? $today08 : $today08->addDay();
    }

    /** Calculate when ticket sales open for a draw. */
    public static function nextOpensAt(string $type, Carbon $drawAt): Carbon
    {
        // Both daily and weekly: ticket sales open at midnight of the draw day
        return $drawAt->copy()->startOfDay();
    }

    /** Seconds until draw. */
    public function secondsUntilDraw(): int
    {
        return max(0, (int) now()->diffInSeconds($this->draw_at, false));
    }

    /** Seconds until ticket sales close (1 hour before draw for daily; at draw_at for weekly). */
    public function secondsUntilClose(): int
    {
        $cutoff = $this->type === 'daily'
            ? $this->draw_at->copy()->subHour()
            : $this->draw_at;
        return max(0, (int) now()->diffInSeconds($cutoff, false));
    }
}
