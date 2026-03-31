<?php

namespace App\Services\TienLen;

/**
 * Card encoding: value = rankIndex * 4 + suitIndex
 * Ranks: 3,4,5,6,7,8,9,10,J,Q,K,A,2  (index 0–12)
 * Suits: S,C,D,H                       (index 0–3)
 * So 3♠=0 (lowest), 2♥=51 (highest)
 */
class Deck
{
    public const RANKS = ['3','4','5','6','7','8','9','10','J','Q','K','A','2'];
    public const SUITS = ['S','C','D','H']; // Spades, Clubs, Diamonds, Hearts

    /** Return a shuffled array of 52 card integers */
    public static function shuffled(): array
    {
        $cards = range(0, 51);
        shuffle($cards);
        return $cards;
    }

    /** Deal 52 cards to 4 players, each 13 cards */
    public static function deal(): array
    {
        $deck = self::shuffled();
        return array_chunk($deck, 13);
    }

    public static function rankIndex(int $card): int
    {
        return intdiv($card, 4);
    }

    public static function suitIndex(int $card): int
    {
        return $card % 4;
    }

    public static function rankName(int $card): string
    {
        return self::RANKS[self::rankIndex($card)];
    }

    public static function suitName(int $card): string
    {
        return self::SUITS[self::suitIndex($card)];
    }

    /** Human-readable label, e.g. "A♥" */
    public static function label(int $card): string
    {
        $suitSymbol = ['♠','♣','♦','♥'][self::suitIndex($card)];
        return self::rankName($card) . $suitSymbol;
    }

    /** Is this card a 2? */
    public static function isTwo(int $card): bool
    {
        return self::rankIndex($card) === 12;
    }
}
