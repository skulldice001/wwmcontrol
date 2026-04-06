<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotteryDraw extends Model
{
    protected $fillable = [
        'type', 'status', 'draw_at', 'opens_at', 'drawn_at',
        'winning_numbers', 'pick_count', 'multiplier', 'ticket_price',
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

    // Jackpot: 000–999 (3-digit number)
    const JACKPOT_NUMBER_MIN = 0;
    const JACKPOT_NUMBER_MAX = 999;

    const DAILY_MULTIPLIER   = 10;
    const WEEKLY_MULTIPLIER  = 70;
    const DAILY_PICK_COUNT   = 2;
    const WEEKLY_PICK_COUNT  = 1;
    const JACKPOT_PICK_COUNT = 1;
    const JACKPOT_PRICE      = 500;

    public function tickets(): HasMany
    {
        return $this->hasMany(LotteryTicket::class);
    }

    public function isOpen(): bool
    {
        if ($this->status !== 'open') return false;
        if ($this->opens_at && now()->lt($this->opens_at)) return false;
        // Daily: ticket sales close 1 hour before draw (19:00); Weekly/Jackpot: close at draw time
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

    /** Return (or create) the current open jackpot draw.
     *  Draw time: daily at 20:30. Pot carries over if no winner.
     */
    public static function getOrCreateJackpot(int $carryoverPot = 0): self
    {
        $draw = self::where('type', 'jackpot')
            ->where('status', 'open')
            ->where('draw_at', '>', now())
            ->latest('draw_at')
            ->first();
        if ($draw) return $draw;

        // Check if one already exists for next draw_at
        $drawAt = self::nextJackpotDrawAt();
        $existing = self::where('type', 'jackpot')->where('draw_at', $drawAt)->first();
        if ($existing) {
            if ($existing->status !== 'open') $existing->update(['status' => 'open']);
            return $existing;
        }

        return self::create([
            'type'         => 'jackpot',
            'status'       => 'open',
            'draw_at'      => $drawAt,
            'opens_at'     => $drawAt->copy()->startOfDay(),
            'pick_count'   => self::JACKPOT_PICK_COUNT,
            'multiplier'   => 1,
            'ticket_price' => self::JACKPOT_PRICE,
            'total_pot'    => $carryoverPot,
        ]);
    }

    public static function nextJackpotDrawAt(): Carbon
    {
        $today2030 = now()->copy()->setTime(20, 30, 0);
        return now()->lt($today2030) ? $today2030 : $today2030->addDay();
    }

    /** Seconds until jackpot draw (for countdown display). */
    public function secondsUntilJackpotDraw(): int
    {
        if (!$this->draw_at) return 0;
        return max(0, (int) now()->diffInSeconds($this->draw_at, false));
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

        // Daily: 20:00 today, or 20:00 tomorrow if already past
        $today20 = now()->copy()->setTime(20, 0, 0);
        return now()->lt($today20) ? $today20 : $today20->addDay();
    }

    /** Calculate when ticket sales open for a draw. */
    public static function nextOpensAt(string $type, Carbon $drawAt): Carbon
    {
        // Both daily and weekly open immediately after the previous draw settles
        return now();
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
