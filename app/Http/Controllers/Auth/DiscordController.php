<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    /**
     * Redirect the user to the Discord authentication page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('discord')->redirect();
    }

    /**
     * Obtain the user information from Discord.
     */
    public function callback(): RedirectResponse
    {
        try {
            $discordUser = Socialite::driver('discord')->user();
        } catch (\Exception $e) {
            dd($e->getMessage());
            return redirect(config('app.frontend_url'))->with('error', 'Discord authentication failed.');
        }

        $user = User::updateOrCreate([
            'discord_id' => $discordUser->getId(),
        ], [
            'name' => $discordUser->getName(),
            'email' => $discordUser->getEmail(),
            'discord_token' => $discordUser->token,
            'discord_refresh_token' => $discordUser->refreshToken,
            'discord_avatar' => $discordUser->getAvatar(),
        ]);

        Auth::login($user, true);

        return redirect(config('app.frontend_url') . '/dashboard');
    }
}
