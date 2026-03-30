<?php

namespace App\Http\Controllers;

use App\Models\LotteryDraw;
use App\Models\LotteryTicket;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LotteryController extends Controller
{
    /** GET /entertainment/lottery */
    public function index()
    {
        $daily  = LotteryDraw::getOrCreateOpen('daily');
        $weekly = LotteryDraw::getOrCreateOpen('weekly');

        $userId = Auth::id();

        $dailyTickets  = LotteryTicket::where('lottery_draw_id', $daily->id)
            ->where('user_id', $userId)->get();
        $weeklyTickets = LotteryTicket::where('lottery_draw_id', $weekly->id)
            ->where('user_id', $userId)->get();

        $recentDaily  = LotteryDraw::where('type', 'daily')
            ->where('status', 'settled')
            ->orderByDesc('draw_at')->take(7)->get();
        $recentWeekly = LotteryDraw::where('type', 'weekly')
            ->where('status', 'settled')
            ->orderByDesc('draw_at')->take(5)->get();

        $balance    = Auth::user()->z_coins;
        $myHistory  = $this->myHistory($userId);

        $lastSettledDaily  = $this->lastSettledDraw('daily',  $userId);
        $lastSettledWeekly = $this->lastSettledDraw('weekly', $userId);

        return view('entertainment.lottery', compact(
            'daily', 'weekly',
            'dailyTickets', 'weeklyTickets',
            'recentDaily', 'recentWeekly',
            'balance', 'myHistory',
            'lastSettledDaily', 'lastSettledWeekly'
        ));
    }

    /** GET /entertainment/lottery/state — AJAX polling */
    public function state()
    {
        $userId = Auth::id();

        $daily  = LotteryDraw::getOrCreateOpen('daily');
        $weekly = LotteryDraw::getOrCreateOpen('weekly');

        return response()->json([
            'daily'                => $this->drawData($daily, $userId),
            'weekly'               => $this->drawData($weekly, $userId),
            'balance'              => Auth::user()->z_coins,
            'recent_daily'         => $this->recentDraws('daily'),
            'recent_weekly'        => $this->recentDraws('weekly'),
            'my_history'           => $this->myHistory($userId),
            'last_settled_daily'   => $this->lastSettledDraw('daily',  $userId),
            'last_settled_weekly'  => $this->lastSettledDraw('weekly', $userId),
        ]);
    }

    /** POST /entertainment/lottery/ticket */
    public function buyTicket(Request $request)
    {
        $request->validate([
            'draw_id'       => 'required|integer',
            'picked_number' => 'required|integer|min:1|max:99',
            'bet_amount'    => 'required|integer|in:10,100,1000,10000',
        ]);

        $draw = LotteryDraw::find($request->draw_id);
        if (!$draw || !$draw->isOpen()) {
            return response()->json(['error' => 'Giải xổ số đã đóng hoặc không tồn tại.'], 422);
        }

        $user   = Auth::user();
        $amount = (int) $request->bet_amount;

        // One ticket per draw per user
        if (LotteryTicket::where('lottery_draw_id', $draw->id)->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'Bạn đã mua vé cho giải này rồi.'], 422);
        }

        $ticket = DB::transaction(function () use ($user, $draw, $request, $amount) {
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            $available = $user->z_coins - $user->z_coins_frozen;
            if ($available < $amount) {
                throw new \Exception('Không đủ Zoo để mua vé.');
            }

            $balBefore = $user->z_coins;
            $user->decrement('z_coins', $amount);

            ZooCoinTransaction::create([
                'user_id'        => $user->id,
                'type'           => 'lottery_bet',
                'amount'         => $amount,
                'balance_before' => $balBefore,
                'balance_after'  => $balBefore - $amount,
                'note'           => 'Mua vé xổ số ' . ($draw->type === 'weekly' ? 'tuần' : 'ngày'),
            ]);

            $ticket = LotteryTicket::create([
                'lottery_draw_id' => $draw->id,
                'user_id'         => $user->id,
                'picked_number'   => (int) $request->picked_number,
                'bet_amount'      => $amount,
            ]);

            // Update draw totals
            $draw->increment('total_tickets');
            $draw->increment('total_pot', $amount);

            return $ticket;
        });

        return response()->json([
            'ok'      => true,
            'ticket'  => $ticket,
            'balance' => Auth::user()->fresh()->z_coins,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function drawData(LotteryDraw $draw, int $userId): array
    {
        $myTickets = LotteryTicket::where('lottery_draw_id', $draw->id)
            ->where('user_id', $userId)
            ->get()
            ->map(fn($t) => [
                'id'             => $t->id,
                'picked_number'  => $t->picked_number,
                'bet_amount'     => $t->bet_amount,
                'is_winner'      => $t->is_winner,
                'payout'         => $t->payout,
            ])->values()->toArray();

        return [
            'id'                => $draw->id,
            'type'              => $draw->type,
            'status'            => $draw->status,
            'draw_at'           => $draw->draw_at->toIso8601String(),
            'opens_at'          => $draw->opens_at?->toIso8601String(),
            'seconds_left'      => $draw->secondsUntilDraw(),
            'seconds_until_open'=> $draw->secondsUntilOpen(),
            'winning_numbers'   => $draw->winning_numbers,
            'multiplier'        => $draw->multiplier,
            'pick_count'        => $draw->pick_count,
            'total_tickets'     => $draw->total_tickets,
            'total_pot'         => $draw->total_pot,
            'my_tickets'        => $myTickets,
        ];
    }

    private function myHistory(int $userId): array
    {
        return LotteryTicket::where('user_id', $userId)
            ->with(['draw:id,type,draw_at,winning_numbers,status'])
            ->orderByDesc('created_at')
            ->take(20)
            ->get()
            ->map(fn($t) => [
                'id'             => $t->id,
                'draw_type'      => $t->draw?->type,
                'draw_at'        => $t->draw?->draw_at?->format('d/m H:i'),
                'draw_status'    => $t->draw?->status,
                'picked_number'  => $t->picked_number,
                'bet_amount'     => $t->bet_amount,
                'is_winner'      => $t->is_winner,
                'payout'         => $t->payout,
            ])->toArray();
    }

    private function lastSettledDraw(string $type, int $userId): ?array
    {
        $draw = LotteryDraw::where('type', $type)
            ->where('status', 'settled')
            ->orderByDesc('draw_at')
            ->first();

        if (!$draw) return null;

        $ticket = LotteryTicket::where('lottery_draw_id', $draw->id)
            ->where('user_id', $userId)
            ->first();

        return [
            'id'              => $draw->id,
            'draw_at'         => $draw->draw_at->format('d/m H:i'),
            'winning_numbers' => $draw->winning_numbers,
            'total_tickets'   => $draw->total_tickets,
            'total_payout'    => $draw->total_payout,
            'my_ticket'       => $ticket ? [
                'picked_number' => $ticket->picked_number,
                'bet_amount'    => $ticket->bet_amount,
                'is_winner'     => $ticket->is_winner,
                'payout'        => $ticket->payout,
            ] : null,
        ];
    }

    private function recentDraws(string $type): array
    {
        return LotteryDraw::where('type', $type)
            ->where('status', 'settled')
            ->orderByDesc('draw_at')
            ->take($type === 'weekly' ? 5 : 7)
            ->get()
            ->map(fn($d) => [
                'id'              => $d->id,
                'draw_at'         => $d->draw_at->format('d/m H:i'),
                'winning_numbers' => $d->winning_numbers,
                'total_tickets'   => $d->total_tickets,
                'total_pot'       => $d->total_pot,
                'total_payout'    => $d->total_payout,
            ])->toArray();
    }
}
