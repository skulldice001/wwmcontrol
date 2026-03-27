<?php

namespace App\Services\Blackjack;

use App\Models\BlackjackGame;
use App\Models\BlackjackTable;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Support\Facades\DB;

class BlackjackEngine
{
    const SUITS = ['spades', 'hearts', 'diamonds', 'clubs'];
    const RANKS = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Deduct bet, deal initial cards, persist game.
     * Returns ['ok' => bool, 'game' => BlackjackGame|null, 'error' => string]
     */
    public static function deal(BlackjackTable $table, int $userId, int $bet): array
    {
        return DB::transaction(function () use ($table, $userId, $bet) {
            $user      = User::where('id', $userId)->lockForUpdate()->first();
            $available = $user->z_coins - $user->z_coins_frozen;

            if ($bet < $table->min_bet || $bet > $table->max_bet) {
                return ['ok' => false, 'error' => __('messages.bj_invalid_bet',
                    [':min' => number_format($table->min_bet), ':max' => number_format($table->max_bet)])];
            }
            if ($available < $bet) {
                return ['ok' => false, 'error' => __('messages.bj_insufficient_funds')];
            }

            $balBefore = $user->z_coins;
            $user->decrement('z_coins', $bet);
            ZooCoinTransaction::create([
                'user_id'        => $user->id,
                'type'           => 'blackjack_bet',
                'amount'         => -$bet,
                'balance_before' => $balBefore,
                'balance_after'  => $balBefore - $bet,
                'note'           => "Blackjack đặt cược (bàn #{$table->id})",
            ]);

            $deck        = self::freshDeck();
            $playerCards = [array_shift($deck), array_shift($deck)];
            $dealerCards = [array_shift($deck), array_shift($deck)];
            $isBlackjack = self::isBlackjack($playerCards);
            $dealerBj    = self::isBlackjack($dealerCards);

            $state = [
                'phase'            => 'playing',
                'bet'              => $bet,
                'deck'             => $deck,
                'player_cards'     => $playerCards,
                'dealer_cards'     => $dealerCards,
                'is_blackjack'     => $isBlackjack,
                'dealer_blackjack' => $dealerBj,
                'can_double'       => ($available - $bet) >= $bet,
                'result'           => null,
                'payout'           => 0,
            ];

            // Immediate finish on player blackjack (dealer hole card checked server-side)
            if ($isBlackjack) {
                $state = self::resolve($state);
                if ($state['payout'] > 0) {
                    $balBefore = $user->z_coins;
                    $user->increment('z_coins', $state['payout']);
                    ZooCoinTransaction::create([
                        'user_id'        => $user->id,
                        'type'           => 'blackjack_payout',
                        'amount'         => $state['payout'],
                        'balance_before' => $balBefore,
                        'balance_after'  => $balBefore + $state['payout'],
                        'note'           => "Blackjack thắng: {$state['result']} +{$state['payout']} Zoo (bàn #{$table->id})",
                    ]);
                    $state['payout'] = 0;
                }
            }

            // Replace any previous game for this user+table
            BlackjackGame::where('user_id', $userId)
                ->where('blackjack_table_id', $table->id)
                ->delete();

            $game = BlackjackGame::create([
                'blackjack_table_id' => $table->id,
                'user_id'            => $userId,
                'state'              => $state,
            ]);

            return ['ok' => true, 'game' => $game];
        });
    }

    /**
     * Process player action: hit | stand | double
     */
    public static function processAction(BlackjackGame $game, string $action): BlackjackGame
    {
        return DB::transaction(function () use ($game, $action) {
            $state = $game->state;

            if ($state['phase'] !== 'playing') {
                return $game;
            }

            switch ($action) {
                case 'hit':
                    $state['player_cards'][] = array_shift($state['deck']);
                    $state['can_double']     = false;
                    if (self::score($state['player_cards']) > 21) {
                        $state = self::resolve($state);
                    }
                    break;

                case 'stand':
                    $state = self::dealerDraw($state);
                    $state = self::resolve($state);
                    break;

                case 'double':
                    if (!$state['can_double']) break;
                    $user  = User::where('id', $game->user_id)->lockForUpdate()->first();
                    $avail = $user->z_coins - $user->z_coins_frozen;
                    if ($avail >= $state['bet']) {
                        $extraBet  = $state['bet'];
                        $balBefore = $user->z_coins;
                        $user->decrement('z_coins', $extraBet);
                        ZooCoinTransaction::create([
                            'user_id'        => $game->user_id,
                            'type'           => 'blackjack_bet',
                            'amount'         => -$extraBet,
                            'balance_before' => $balBefore,
                            'balance_after'  => $balBefore - $extraBet,
                            'note'           => "Blackjack đôi (bàn #{$game->blackjack_table_id})",
                        ]);
                        $state['bet'] *= 2;
                    }
                    $state['can_double']     = false;
                    $state['player_cards'][] = array_shift($state['deck']);
                    if (self::score($state['player_cards']) > 21) {
                        $state = self::resolve($state);
                    } else {
                        $state = self::dealerDraw($state);
                        $state = self::resolve($state);
                    }
                    break;
            }

            // Credit payout immediately on finish
            if (($state['payout'] ?? 0) > 0) {
                $payer     = User::where('id', $game->user_id)->lockForUpdate()->first();
                $balBefore = $payer->z_coins;
                $payer->increment('z_coins', $state['payout']);
                ZooCoinTransaction::create([
                    'user_id'        => $game->user_id,
                    'type'           => 'blackjack_payout',
                    'amount'         => $state['payout'],
                    'balance_before' => $balBefore,
                    'balance_after'  => $balBefore + $state['payout'],
                    'note'           => "Blackjack thắng: {$state['result']} +{$state['payout']} Zoo (bàn #{$game->blackjack_table_id})",
                ]);
                $state['payout'] = 0;
            }

            $game->update(['state' => $state]);
            return $game->fresh();
        });
    }

    /**
     * Build client-safe state: hides deck and dealer hole card during play.
     */
    public static function clientState(BlackjackGame $game, User $user): array
    {
        $s = $game->fresh()->state;

        $dealerCards = $s['dealer_cards'];
        if ($s['phase'] === 'playing') {
            $dealerCards[1] = ['rank' => 'back', 'suit' => 'back'];
        }

        $visibleDealer = array_values(array_filter($dealerCards, fn($c) => ($c['suit'] ?? '') !== 'back'));
        $dealerScore   = $s['phase'] === 'finished'
            ? self::score($s['dealer_cards'])
            : self::score($visibleDealer);

        $fresh = $user->fresh();
        return [
            'phase'        => $s['phase'],
            'bet'          => $s['bet'],
            'player_cards' => $s['player_cards'],
            'dealer_cards' => $dealerCards,
            'player_score' => self::score($s['player_cards']),
            'dealer_score' => $dealerScore,
            'is_blackjack' => $s['is_blackjack'],
            'dealer_bj'    => $s['phase'] === 'finished' ? $s['dealer_blackjack'] : false,
            'can_double'   => $s['can_double'] && $s['phase'] === 'playing',
            'result'       => $s['result'],
            'z_coins'      => $fresh->z_coins - $fresh->z_coins_frozen,
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private static function freshDeck(): array
    {
        $deck = [];
        foreach (self::SUITS as $suit) {
            foreach (self::RANKS as $rank) {
                $deck[] = ['rank' => $rank, 'suit' => $suit];
            }
        }
        shuffle($deck);
        return $deck;
    }

    public static function score(array $cards): int
    {
        $total = $aces = 0;
        foreach ($cards as $c) {
            if (($c['suit'] ?? '') === 'back') continue;
            $r = $c['rank'];
            if ($r === 'A')                            { $aces++; $total += 11; }
            elseif (in_array($r, ['J','Q','K','10'])) { $total += 10; }
            else                                       { $total += (int)$r; }
        }
        while ($total > 21 && $aces > 0) { $total -= 10; $aces--; }
        return $total;
    }

    private static function isBlackjack(array $cards): bool
    {
        return count($cards) === 2 && self::score($cards) === 21;
    }

    private static function dealerDraw(array $state): array
    {
        // Dealer hits until hard 17+
        while (self::score($state['dealer_cards']) < 17) {
            $state['dealer_cards'][] = array_shift($state['deck']);
        }
        return $state;
    }

    private static function resolve(array $state): array
    {
        $p   = self::score($state['player_cards']);
        $d   = self::score($state['dealer_cards']);
        $bet = $state['bet'];
        $bj  = $state['is_blackjack'];
        $dbj = $state['dealer_blackjack'];

        if ($p > 21) {
            [$result, $payout] = ['bust',        0];
        } elseif ($bj && $dbj) {
            [$result, $payout] = ['push',        $bet];
        } elseif ($bj) {
            [$result, $payout] = ['blackjack',   $bet + (int) round($bet * 1.5)]; // 3:2
        } elseif ($dbj) {
            [$result, $payout] = ['lose',        0];
        } elseif ($d > 21) {
            [$result, $payout] = ['dealer_bust', $bet * 2];
        } elseif ($p > $d) {
            [$result, $payout] = ['win',         $bet * 2];
        } elseif ($p === $d) {
            [$result, $payout] = ['push',        $bet];
        } else {
            [$result, $payout] = ['lose',        0];
        }

        $state['phase']  = 'finished';
        $state['result'] = $result;
        $state['payout'] = $payout;
        return $state;
    }
}
