<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PokerTable;

class PokerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PokerTable::create([
            'name' => 'Beginner Table 1',
            'small_blind' => 10,
            'big_blind' => 20,
            'min_buy_in' => 1000,
            'max_buy_in' => 2000,
            'current_players' => 3,
            'max_players' => 9,
            'status' => 'playing',
        ]);

        PokerTable::create([
            'name' => 'Beginner Table 2',
            'small_blind' => 10,
            'big_blind' => 20,
            'min_buy_in' => 1000,
            'max_buy_in' => 2000,
            'current_players' => 0,
            'max_players' => 9,
            'status' => 'waiting',
        ]);

        PokerTable::create([
            'name' => 'Pro Table',
            'small_blind' => 50,
            'big_blind' => 100,
            'min_buy_in' => 5000,
            'max_buy_in' => 10000,
            'current_players' => 5,
            'max_players' => 6,
            'status' => 'playing',
        ]);
        
        PokerTable::create([
            'name' => 'High Rollers',
            'small_blind' => 500,
            'big_blind' => 1000,
            'min_buy_in' => 50000,
            'max_buy_in' => 200000,
            'current_players' => 2,
            'max_players' => 6,
            'status' => 'waiting',
        ]);
    }
}
