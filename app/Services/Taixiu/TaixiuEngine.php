<?php

namespace App\Services\Taixiu;

use App\Models\TaixiuGame;
use App\Models\TaixiuTable;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Support\Facades\DB;

class TaixiuEngine
{
    const BETTING_DURATION = 30; // seconds
    const RESULT_DURATION  = 8;  // seconds

    // -------------------------------------------------------------------------

    public static function startRound(TaixiuTable $table): TaixiuGame
    {
        $betDeadline = time() + self::BETTING_DURATION;

        $state = [
            'phase'              => 'betting',
            'dice'               => null,
            'sum'                => null,
            'outcome'            => null,
            'bet_deadline_at'    => $betDeadline,
            'result_deadline_at' => null,
            'bets'               => [],
            'log'                => ['Ván mới bắt đầu. Đặt cược trong ' . self::BETTING_DURATION . ' giây.'],
        ];

        return TaixiuGame::create([
            'taixiu_table_id' => $table->id,
            'state'           => $state,
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * Place (or replace) a bet for a user.
     * Deducts z_coins immediately. Returns ['ok', 'error', 'game'].
     */
    public static function placeBet(
        TaixiuGame $game,
        int $userId,
        string $choice,   // 'tai' | 'xiu'
        int $amount,
        TaixiuTable $table
    ): array {
        $state = $game->state;

        if ($state['phase'] !== 'betting') {
            return ['ok' => false, 'error' => 'Đã hết thời gian đặt cược.'];
        }

        if (!in_array($choice, ['tai', 'xiu'])) {
            return ['ok' => false, 'error' => 'Lựa chọn không hợp lệ.'];
        }

        if ($amount < $table->min_bet || $amount > $table->max_bet) {
            return ['ok' => false, 'error' => "Cược từ {$table->min_bet} đến {$table->max_bet} Zoo."];
        }

        return DB::transaction(function () use ($game, $userId, $choice, $amount, $state) {
            $user      = User::where('id', $userId)->lockForUpdate()->first();
            $available = $user->z_coins - $user->z_coins_frozen;

            // Refund previous bet from this user if any
            $prevBet = null;
            foreach ($state['bets'] as $b) {
                if ($b['user_id'] === $userId) { $prevBet = $b; break; }
            }

            $netCost = $amount - ($prevBet ? $prevBet['amount'] : 0);

            if ($available < $netCost) {
                return ['ok' => false, 'error' => 'Không đủ Zoo.'];
            }

            // Remove old bet entry
            $state['bets'] = array_values(array_filter(
                $state['bets'],
                fn($b) => $b['user_id'] !== $userId
            ));

            // Refund old bet z_coins if replacing
            if ($prevBet) {
                $user->increment('z_coins', $prevBet['amount']);
            }

            // Deduct new bet
            $balBefore = $user->fresh()->z_coins;
            $user->decrement('z_coins', $amount);

            ZooCoinTransaction::create([
                'user_id'        => $userId,
                'type'           => 'taixiu_bet',
                'amount'         => $amount,
                'balance_before' => $balBefore,
                'balance_after'  => $balBefore - $amount,
                'note'           => 'Tài Xỉu cược',
            ]);

            $label        = $choice === 'tai' ? 'Tài' : 'Xỉu';
            $state['bets'][] = [
                'user_id' => $userId,
                'name'    => $user->name,
                'choice'  => $choice,
                'amount'  => $amount,
                'payout'  => null,
                'result'  => null,
            ];
            $state['log'][] = "{$user->name} cược " . number_format($amount) . " Zoo vào {$label}.";

            $game->update(['state' => $state]);
            return ['ok' => true, 'error' => null, 'game' => $game->fresh()];
        });
    }

    // -------------------------------------------------------------------------

    /** Roll dice, settle all bets, transition to result phase. */
    public static function roll(TaixiuGame $game): TaixiuGame
    {
        $state = $game->state;
        if ($state['phase'] !== 'betting') return $game;

        $dice   = [random_int(1, 6), random_int(1, 6), random_int(1, 6)];
        $sum    = array_sum($dice);
        $triple = ($dice[0] === $dice[1] && $dice[1] === $dice[2]);

        if ($triple) {
            $outcome = 'triple';
        } elseif ($sum <= 10) {
            $outcome = 'xiu';
        } else {
            $outcome = 'tai';
        }

        $outcomeLabel = match ($outcome) {
            'triple' => 'Ba bằng nhau — Nhà cái thắng',
            'tai'    => "Tài ({$sum})",
            'xiu'    => "Xỉu ({$sum})",
        };

        $resultDeadline = time() + self::RESULT_DURATION;

        // Settle bets
        foreach ($state['bets'] as &$bet) {
            if ($triple) {
                $bet['result'] = 'triple';
                $bet['payout'] = 0;
            } elseif ($bet['choice'] === $outcome) {
                $bet['result'] = 'win';
                $bet['payout'] = $bet['amount'] * 2; // return bet + 1:1 win
            } else {
                $bet['result'] = 'lose';
                $bet['payout'] = 0;
            }
        }
        unset($bet);

        $state['phase']              = 'result';
        $state['dice']               = $dice;
        $state['sum']                = $sum;
        $state['outcome']            = $outcome;
        $state['result_deadline_at'] = $resultDeadline;
        $state['log'][]              = "Xúc xắc: {$dice[0]}-{$dice[1]}-{$dice[2]} = {$sum} → {$outcomeLabel}";

        $game->update(['state' => $state]);
        $game = $game->fresh();

        // Pay out winners
        foreach ($game->state['bets'] as $bet) {
            if ($bet['result'] === 'win' && $bet['payout'] > 0) {
                $user      = User::find($bet['user_id']);
                if (!$user) continue;
                $balBefore = $user->z_coins;
                $user->increment('z_coins', $bet['payout']);
                ZooCoinTransaction::create([
                    'user_id'        => $bet['user_id'],
                    'type'           => 'taixiu_payout',
                    'amount'         => $bet['payout'],
                    'balance_before' => $balBefore,
                    'balance_after'  => $balBefore + $bet['payout'],
                    'note'           => 'Tài Xỉu thắng',
                ]);
            }
        }

        return $game;
    }

    // -------------------------------------------------------------------------

    /** Build client-safe state payload for a given viewer. */
    public static function clientState(TaixiuGame $game, ?int $viewerUserId = null): array
    {
        $state  = $game->state;
        $myBet  = null;
        $myBal  = null;

        if ($viewerUserId !== null) {
            foreach ($state['bets'] as $b) {
                if ($b['user_id'] === $viewerUserId) { $myBet = $b; break; }
            }
            $user  = User::find($viewerUserId);
            $myBal = $user ? ($user->z_coins - $user->z_coins_frozen) : 0;
        }

        $deadline = $state['phase'] === 'betting'
            ? ($state['bet_deadline_at']    ?? null)
            : ($state['result_deadline_at'] ?? null);

        return [
            'game_id'  => $game->id,
            'phase'    => $state['phase'],
            'dice'     => $state['dice'],
            'sum'      => $state['sum'],
            'outcome'  => $state['outcome'],
            'deadline' => $deadline,
            'bets'     => $state['bets'],
            'my_bet'   => $myBet,
            'z_coins'  => $myBal,
            'log'      => array_slice($state['log'] ?? [], -8),
        ];
    }
}
