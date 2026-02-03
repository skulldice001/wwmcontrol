<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InnerWay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    /**
     * Redirect the user to the Discord authentication page.
     */
    public function redirect(): RedirectResponse
    {
        try {
            return Socialite::driver('discord')->redirect();
        } catch (\Exception $e) {
            \Log::error('Discord Redirect Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('login')->with('error', 'Failed to connect to Discord.');
        }
    }

    /**
     * Obtain the user information from Discord.
     */
    public function callback(\Illuminate\Http\Request $request): RedirectResponse
    {
        try {
            $discordUser = Socialite::driver('discord')->user();
        } catch (\Exception $e) {
            \Log::error('Discord Auth Callback Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect(config('app.frontend_url'))->with('error', 'Discord authentication failed.');
        }

        // Invalidate old session and regenerate token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user = User::where('discord_id', $discordUser->getId())->first();

        // Ensure all inner ways exist in the database
        $innerWayFiles = File::files(resource_path('icon/inner_way'));
        $allInnerWayIds = [];
        foreach ($innerWayFiles as $file) {
            $filenameWithExt = $file->getFilename();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            // First creation: color will be set by heuristic.
            // Existing ones: color will NOT be overwritten here, as it might have been set by Seeder.
            $innerWayData = [
                'name' => str_replace('-', ' ', $filename),
                'icon' => $filenameWithExt,
            ];

            $innerWay = InnerWay::where('slug', Str::slug($filename))->first();
            if (!$innerWay) {
                $color = 'blue';
                if ($file->getExtension() === 'png') {
                    $color = 'gold';
                }
                $innerWayData['color'] = $color;
                $innerWayData['slug'] = Str::slug($filename);
                $innerWay = InnerWay::create($innerWayData);
            } else {
                $innerWay->update($innerWayData);
            }
            $allInnerWayIds[$innerWay->id] = ['level' => 1];
        }

        if (!$user) {
            $user = User::create([
                'discord_id' => $discordUser->getId(),
                'name' => $discordUser->getName(),
                'email' => $discordUser->getEmail(),
                'discord_token' => $discordUser->token,
                'discord_refresh_token' => $discordUser->refreshToken,
                'discord_avatar' => $discordUser->getAvatar(),
            ]);

            // Sync with default level 1
            $user->innerWays()->sync($allInnerWayIds);
        } else {
            $user->update([
                'name' => $discordUser->getName(),
                'email' => $discordUser->getEmail(),
                'discord_token' => $discordUser->token,
                'discord_refresh_token' => $discordUser->refreshToken,
                'discord_avatar' => $discordUser->getAvatar(),
            ]);

            // Get current inner ways
            $userInnerWays = $user->innerWays()->get();

            if ($userInnerWays->isEmpty()) {
                $user->innerWays()->sync($allInnerWayIds);
            } else {
                // Update logic: pick a random one that is not level 6 and increase it
                $eligible = $userInnerWays->filter(fn($iw) => $iw->pivot->level < 6);
                if ($eligible->isNotEmpty()) {
                    $randomInnerWay = $eligible->random();
                    $user->innerWays()->updateExistingPivot($randomInnerWay->id, [
                        'level' => $randomInnerWay->pivot->level + 1
                    ]);
                }
            }
        }

        Auth::login($user, true);

        return redirect(config('app.frontend_url') . '/dashboard');
    }
}
