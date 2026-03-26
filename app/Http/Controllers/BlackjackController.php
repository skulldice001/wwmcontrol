<?php

namespace App\Http\Controllers;

use App\Models\BlackjackGame;
use App\Models\BlackjackTable;
use App\Services\Blackjack\BlackjackEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlackjackController extends Controller
{
    public function show(BlackjackTable $table)
    {
        return view('entertainment.blackjack_room', compact('table'));
    }

    public function state(BlackjackTable $table)
    {
        $user = Auth::user();
        $game = BlackjackGame::where('user_id', $user->id)
            ->where('blackjack_table_id', $table->id)
            ->latest()
            ->first();

        $fresh = $user->fresh();
        $base  = [
            'z_coins' => $fresh->z_coins - $fresh->z_coins_frozen,
            'min_bet' => $table->min_bet,
            'max_bet' => $table->max_bet,
        ];

        if (!$game) {
            return response()->json(array_merge($base, ['phase' => 'betting']));
        }

        $state = BlackjackEngine::clientState($game, $user);
        return response()->json(array_merge($base, ['state' => $state]));
    }

    public function deal(Request $request, BlackjackTable $table)
    {
        $request->validate(['bet' => 'required|integer|min:1']);
        $result = BlackjackEngine::deal($table, Auth::id(), (int) $request->bet);

        if (!$result['ok']) {
            return response()->json(['error' => $result['error']], 422);
        }

        $state = BlackjackEngine::clientState($result['game'], Auth::user());
        return response()->json(['state' => $state]);
    }

    public function action(Request $request, BlackjackTable $table)
    {
        $request->validate(['action' => 'required|in:hit,stand,double']);

        $user = Auth::user();
        $game = BlackjackGame::where('user_id', $user->id)
            ->where('blackjack_table_id', $table->id)
            ->latest()
            ->first();

        if (!$game || $game->state['phase'] !== 'playing') {
            return response()->json(['error' => __('messages.bj_no_active_hand')], 422);
        }

        $game  = BlackjackEngine::processAction($game, $request->action);
        $state = BlackjackEngine::clientState($game, $user);
        return response()->json(['state' => $state]);
    }
}
