<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PokerTable;
use App\Events\PokerTableUpdated;

class EntertainmentController extends Controller
{
    public function index()
    {
        return view('entertainment.index');
    }

    public function poker()
    {
        try {
            $tables = PokerTable::all();
        } catch (\Exception $e) {
            $tables = collect([
                new PokerTable(['id' => 1, 'name' => 'Demo Table 1', 'blinds' => '1/2', 'buy_in_min' => 100, 'buy_in_max' => 200, 'max_players' => 9, 'current_players' => 5, 'status' => 'playing']),
                new PokerTable(['id' => 2, 'name' => 'Demo Table 2', 'blinds' => '2/5', 'buy_in_min' => 200, 'buy_in_max' => 500, 'max_players' => 6, 'current_players' => 0, 'status' => 'waiting']),
            ]);
        }
        return view('entertainment.poker', compact('tables'));
    }

    public function blackjack()
    {
        return view('entertainment.blackjack');
    }

    public function testUpdate()
    {
        try {
            $table = PokerTable::inRandomOrder()->first();
            if ($table) {
                $table->current_players = rand(0, $table->max_players);
                if ($table->current_players == $table->max_players) {
                    $table->status = 'full';
                } elseif ($table->current_players > 0) {
                    $table->status = 'playing';
                } else {
                    $table->status = 'waiting';
                }
                $table->save();
            } else {
                throw new \Exception("No tables found");
            }
        } catch (\Exception $e) {
            $table = new PokerTable([
                'id' => rand(1, 2),
                'name' => 'Demo Table ' . rand(1, 2),
                'blinds' => '1/2',
                'buy_in_min' => 100,
                'buy_in_max' => 200,
                'max_players' => 9,
                'current_players' => rand(0, 9),
                'status' => 'playing'
            ]);
            
            if ($table->current_players == $table->max_players) {
                $table->status = 'full';
            } elseif ($table->current_players > 0) {
                $table->status = 'playing';
            } else {
                $table->status = 'waiting';
            }
        }

        event(new PokerTableUpdated($table));
        
        return response()->json(['success' => true, 'table' => $table]);
    }
}
