<?php

namespace App\Http\Controllers;

use App\Events\TienLenTableUpdated;
use App\Jobs\TienLenAutoPassJob;
use App\Models\TienLenGame;
use App\Models\TienLenMessage;
use App\Models\TienLenTable;
use App\Models\TienLenTablePlayer;
use App\Services\TienLen\GameEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TienLenController extends Controller
{
    // ─── Lobby ──────────────────────────────────────────────────────────────

    public function index()
    {
        // Only show non-AI tables in the public lobby; AI tables are private
        $tables = TienLenTable::with(['owner', 'players'])
            ->where('is_ai_mode', false)
            ->whereIn('status', ['waiting', 'playing'])
            ->latest()
            ->get();

        return view('entertainment.tienlen', compact('tables'));
    }

    public function createTable(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:50',
            'variant'   => 'required|in:mien_bac,mien_nam',
            'entry_fee' => 'required|integer|min:10|max:10000',
        ]);

        $table = TienLenTable::create([
            'owner_id'  => Auth::id(),
            'name'      => $request->name,
            'variant'   => $request->variant,
            'entry_fee' => $request->entry_fee,
            'status'    => 'waiting',
        ]);

        // Auto-join owner
        TienLenTablePlayer::create([
            'tienlen_table_id' => $table->id,
            'user_id'          => Auth::id(),
            'seat'             => 1,
            'is_ready'         => false,
        ]);

        return redirect()->route('entertainment.tienlen.room', $table->id);
    }

    public function createAiTable(Request $request)
    {
        $request->validate([
            'variant' => 'required|in:mien_bac,mien_nam',
        ]);

        $table = TienLenTable::create([
            'owner_id'  => Auth::id(),
            'name'      => Auth::user()->name . ' vs AI',
            'variant'   => $request->variant,
            'entry_fee' => 100,
            'status'    => 'waiting',
            'is_ai_mode' => true,
        ]);

        TienLenTablePlayer::create([
            'tienlen_table_id' => $table->id,
            'user_id'          => Auth::id(),
            'seat'             => 1,
            'is_ready'         => true,
        ]);

        return redirect()->route('entertainment.tienlen.room', $table->id);
    }

    // ─── Room ────────────────────────────────────────────────────────────────

    public function room(TienLenTable $table)
    {
        $user = Auth::user();

        // Check if user is at this table
        $playerRecord = TienLenTablePlayer::where('tienlen_table_id', $table->id)
            ->where('user_id', $user->id)->first();

        if (!$playerRecord && $table->status === 'waiting') {
            // Auto-join if there's room
            $seatCount = $table->tablePlayerRecords()->count();
            if ($seatCount >= 4) {
                return redirect()->route('entertainment.tienlen.index')
                    ->with('error', 'Bàn đã đủ người.');
            }
            $playerRecord = TienLenTablePlayer::create([
                'tienlen_table_id' => $table->id,
                'user_id'          => $user->id,
                'seat'             => $seatCount + 1,
                'is_ready'         => false,
            ]);
        } elseif (!$playerRecord) {
            return redirect()->route('entertainment.tienlen.index')
                ->with('error', 'Bạn không ở bàn này.');
        }

        $game     = $table->activeGame;
        $messages = $table->messages()->with('user')->latest()->limit(50)->get()->reverse()->values();

        // Build my hand (only shown to current user)
        $myHand = [];
        if ($game) {
            foreach ($game->state['players'] as $p) {
                if ($p['user_id'] === $user->id) {
                    $myHand = $p['hand'];
                    break;
                }
            }
        }

        return view('entertainment.tienlen_room', compact('table', 'game', 'messages', 'myHand', 'playerRecord'));
    }

    public function ready(TienLenTable $table)
    {
        $user = Auth::user();
        $rec  = TienLenTablePlayer::where('tienlen_table_id', $table->id)
            ->where('user_id', $user->id)->firstOrFail();

        $rec->update(['is_ready' => !$rec->is_ready]);

        broadcast(new TienLenTableUpdated($table->fresh()->load('players')))->toOthers();

        return response()->json(['ok' => true, 'is_ready' => $rec->is_ready]);
    }

    public function start(TienLenTable $table)
    {
        $user = Auth::user();

        if ($table->owner_id !== $user->id && !$table->is_ai_mode) {
            return response()->json(['error' => 'Chỉ chủ phòng mới được bắt đầu'], 403);
        }

        if ($table->status !== 'waiting') {
            return response()->json(['error' => 'Bàn không ở trạng thái chờ'], 400);
        }

        $players  = $table->tablePlayerRecords()->get();
        $required = $table->is_ai_mode ? 1 : 4;

        if ($players->count() < $required) {
            return response()->json(['error' => "Cần đủ {$required} người để bắt đầu"], 400);
        }

        // Check all ready (non-AI mode)
        if (!$table->is_ai_mode) {
            $notReady = $players->where('is_ready', false)->count();
            if ($notReady > 0) {
                return response()->json(['error' => 'Tất cả người chơi phải sẵn sàng'], 400);
            }
        }

        $game = GameEngine::startGame($table);

        broadcast(new TienLenTableUpdated($table->fresh()->load('players')))->toOthers();

        return response()->json(['ok' => true, 'redirect' => route('entertainment.tienlen.room', $table->id)]);
    }

    public function play(Request $request, TienLenTable $table)
    {
        $request->validate([
            'cards' => 'present|array',
            'cards.*' => 'integer|min:0|max:51',
        ]);

        $game = $table->activeGame;
        if (!$game) {
            return response()->json(['error' => 'Không có ván đang chơi'], 400);
        }

        $result = GameEngine::playCards($game, Auth::id(), $request->cards);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 422);
        }

        $updatedGame = $result['game'];

        // Dispatch auto-pass job for next human player
        $state   = $updatedGame->state;
        $current = $state['current_player'];
        if (!$state['players'][$current]['is_ai'] && $state['phase'] !== 'finished') {
            TienLenAutoPassJob::dispatch($updatedGame->id, $state['players'][$current]['user_id'])
                ->delay(now()->addSeconds(30));
        }

        broadcast(new TienLenTableUpdated($table->fresh()->load('players')))->toOthers();

        return response()->json(['ok' => true]);
    }

    public function state(TienLenTable $table)
    {
        $user  = Auth::user();
        $game  = $table->activeGame;
        $rec   = TienLenTablePlayer::where('tienlen_table_id', $table->id)
            ->where('user_id', $user->id)->first();

        $players = $table->players()->get()->map(fn($p) => [
            'user_id'  => $p->id,
            'name'     => $p->name,
            'is_ready' => (bool) $p->pivot->is_ready,
            'seat'     => $p->pivot->seat,
        ])->values();

        $myHand = [];
        $gameState = null;

        if ($game) {
            foreach ($game->state['players'] as $p) {
                if ($p['user_id'] === $user->id) {
                    $myHand = $p['hand'];
                    break;
                }
            }
            // Strip other players' hands from state
            $stateArr = $game->state;
            foreach ($stateArr['players'] as $idx => $p) {
                if ($p['user_id'] !== $user->id) {
                    $stateArr['players'][$idx]['hand'] = count($p['hand']); // just count
                }
            }
            $gameState = $stateArr;
        }

        return response()->json([
            'table_status' => $table->status,
            'variant'      => $table->variant,
            'is_ai_mode'   => $table->is_ai_mode,
            'players'      => $players,
            'is_ready'     => $rec ? $rec->is_ready : false,
            'game'         => $gameState,
            'my_hand'      => $myHand,
        ]);
    }

    public function chat(Request $request, TienLenTable $table)
    {
        $request->validate(['body' => 'required|string|max:200']);

        $msg = TienLenMessage::create([
            'tienlen_table_id' => $table->id,
            'user_id'          => Auth::id(),
            'body'             => $request->body,
        ]);

        broadcast(new TienLenTableUpdated($table->fresh()->load('players')))->toOthers();

        return response()->json(['ok' => true, 'message' => [
            'id'         => $msg->id,
            'user_name'  => Auth::user()->name,
            'body'       => $msg->body,
            'created_at' => $msg->created_at->toIso8601String(),
        ]]);
    }

    public function leave(TienLenTable $table)
    {
        TienLenTablePlayer::where('tienlen_table_id', $table->id)
            ->where('user_id', Auth::id())->delete();

        if ($table->tablePlayerRecords()->count() === 0) {
            $table->delete();
        }

        broadcast(new TienLenTableUpdated($table->load('players')))->toOthers();

        return redirect()->route('entertainment.tienlen.index');
    }
}
