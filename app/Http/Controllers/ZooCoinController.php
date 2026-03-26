<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZooCoinController extends Controller
{
    public function transfer(Request $request)
    {
        $request->validate([
            'recipient_account' => ['required', 'string', 'max:255'],
            'amount'            => ['required', 'integer', 'min:1'],
            'note'              => ['nullable', 'string', 'max:255'],
        ]);

        $sender    = auth()->user();
        $recipient = User::where('account', $request->recipient_account)->whereNull('deleted_at')->first();

        if (!$recipient) {
            return back()->withInput()->with('zoo_error', __('messages.zcoin_transfer_not_found'));
        }

        if ($recipient->id === $sender->id) {
            return back()->withInput()->with('zoo_error', __('messages.zcoin_transfer_self'));
        }

        $amount    = (int) $request->amount;
        $available = $sender->availableZCoins();

        if ($amount > $available) {
            return back()->withInput()->with('zoo_error', __('messages.zcoin_transfer_insufficient', [
                'available' => number_format($available),
            ]));
        }

        DB::transaction(function () use ($sender, $recipient, $amount, $request) {
            $senderBefore = (int) $sender->z_coins;
            $recvBefore   = (int) $recipient->z_coins;

            $sender->decrement('z_coins', $amount);
            $recipient->increment('z_coins', $amount);

            ZooCoinTransaction::create([
                'user_id'         => $sender->id,
                'type'            => 'transfer_out',
                'amount'          => $amount,
                'balance_before'  => $senderBefore,
                'balance_after'   => $senderBefore - $amount,
                'note'            => $request->note,
                'related_user_id' => $recipient->id,
            ]);

            ZooCoinTransaction::create([
                'user_id'         => $recipient->id,
                'type'            => 'transfer_in',
                'amount'          => $amount,
                'balance_before'  => $recvBefore,
                'balance_after'   => $recvBefore + $amount,
                'note'            => $request->note,
                'related_user_id' => $sender->id,
            ]);
        });

        return back()->with('zoo_success', __('messages.zcoin_transfer_success', [
            'amount'    => number_format($amount),
            'recipient' => $recipient->account,
        ]));
    }

    public function history(Request $request)
    {
        $transactions = auth()->user()
            ->zCoinTransactions()
            ->with(['staff', 'relatedUser'])
            ->latest()
            ->paginate(20);

        return view('zoo_coins.history', compact('transactions'));
    }
}
