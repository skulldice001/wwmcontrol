<?php

namespace Database\Seeders;

use App\Models\BlackjackTable;
use Illuminate\Database\Seeder;

class BlackjackTableSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            ['name' => 'Low Stakes',  'min_bet' => 100,    'max_bet' => 2_000],
            ['name' => 'Mid Stakes',  'min_bet' => 1_000,  'max_bet' => 20_000],
            ['name' => 'High Stakes', 'min_bet' => 10_000, 'max_bet' => 200_000],
        ];

        foreach ($tables as $t) {
            BlackjackTable::firstOrCreate(['name' => $t['name']], $t);
        }
    }
}
