<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'account' => ['required', 'string'],
            'password' => ['required'],
        ]);

        if (Auth::guard('staff')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Logged in successfully']);
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'account' => [trans('auth.failed')],
            ]);
        }

        throw ValidationException::withMessages([
            'account' => trans('auth.failed'),
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($request->wantsJson()) {
            return response()->noContent();
        }

        return redirect()->route('admin.login');
    }
}
