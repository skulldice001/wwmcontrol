<?php

namespace App\Services\Poker;

class Deck
{
    const SUITS = ['spades', 'hearts', 'diamonds', 'clubs'];
    const RANKS = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];
    const VALUES = [
        '2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
        '10'=>10,'J'=>11,'Q'=>12,'K'=>13,'A'=>14,
    ];

    /** Return a shuffled 52-card deck */
    public static function fresh(): array
    {
        $deck = [];
        foreach (self::SUITS as $suit) {
            foreach (self::RANKS as $rank) {
                $deck[] = ['suit' => $suit, 'rank' => $rank];
            }
        }
        shuffle($deck);
        return $deck;
    }

    /** Deal one card from the front of the deck */
    public static function deal(array &$deck): array
    {
        return array_shift($deck);
    }

    /** Deal N cards from the front of the deck */
    public static function dealN(array &$deck, int $n): array
    {
        $cards = [];
        for ($i = 0; $i < $n; $i++) {
            $cards[] = array_shift($deck);
        }
        return $cards;
    }

    /** Public URL for a card image */
    public static function img(array $card): string
    {
        return asset("img/poker/{$card['suit']}_{$card['rank']}.png");
    }
}
