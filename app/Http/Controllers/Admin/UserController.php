<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ZooCoinTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withTrashed()->with(['innerWays', 'mainSkill', 'subSkill'])->get();
        return view('admin.users.index', compact('users'));
    }

    public function restore(User $user)
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user->restore();

        return redirect()->route('admin.users.index')->with('success', __('messages.user_enabled_success'));
    }

    public function destroy(User $user)
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', __('messages.user_disabled_success'));
    }

    public function show(User $user)
    {
        $user->load(['innerWays', 'mainSkill', 'subSkill']);
        return view('admin.users.show', compact('user'));
    }

    public function create()
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name'        => ['nullable', 'string', 'max:255'],
            'account'     => ['required', 'string', 'max:255', 'unique:'.User::class],
            'email'       => ['nullable', 'string', 'email', 'max:255', 'unique:'.User::class],
            'password'    => ['required', 'confirmed', Rules\Password::defaults()],
            'ingame_name' => ['nullable', 'string', 'max:255'],
            'country'     => ['nullable', 'string', 'max:255'],
        ]);

        User::create([
            'name'        => $request->name,
            'account'     => $request->account,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'ingame_name' => $request->ingame_name,
            'country'     => $request->country,
        ]);

        return redirect()->route('admin.users.index')->with('success', __('messages.user_created_success'));
    }

    public function freezeCoins(Request $request, User $user)
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $amount = (int) $request->amount;

        if ($amount > $user->z_coins) {
            return back()->with('error', __('messages.zcoin_freeze_exceed'));
        }

        $user->update(['z_coins_frozen' => $amount]);

        return back()->with('success', __('messages.zcoin_freeze_success'));
    }

    public function adjustCoins(Request $request, User $user)
    {
        $staff = auth()->guard('staff')->user();
        if (!$staff->isMaster()) {
            abort(403, 'Chỉ Master mới có quyền điều chỉnh Zoo-coin.');
        }

        $request->validate([
            'adjust_type'   => ['required', 'in:add,deduct'],
            'adjust_amount' => ['required', 'integer', 'min:1'],
            'adjust_note'   => ['nullable', 'string', 'max:255'],
        ]);

        $type   = $request->adjust_type;
        $amount = (int) $request->adjust_amount;
        $before = (int) $user->z_coins;

        if ($type === 'deduct' && $amount > $before) {
            return back()->with('error', 'Số Zoo-coin không đủ để trừ (hiện có: ' . number_format($before) . ').');
        }

        $after = $type === 'add' ? $before + $amount : $before - $amount;

        $user->update(['z_coins' => $after]);

        ZooCoinTransaction::create([
            'user_id'        => $user->id,
            'type'           => $type,
            'amount'         => $amount,
            'balance_before' => $before,
            'balance_after'  => $after,
            'note'           => $request->adjust_note,
            'staff_id'       => $staff->id,
        ]);

        return back()->with('success', __('messages.zcoin_adjust_success'));
    }

    public function coinHistory(User $user)
    {
        if (!auth()->guard('staff')->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $transactions = $user->zCoinTransactions()
            ->with(['staff', 'relatedUser'])
            ->latest()
            ->paginate(30);

        return view('admin.users.coin_history', compact('user', 'transactions'));
    }
}
