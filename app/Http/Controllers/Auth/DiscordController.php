<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InnerWay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\DiscordService;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    protected $discordService;

    public function __construct(DiscordService $discordService)
    {
        $this->discordService = $discordService;
    }

    /**
     * Redirect the user to the Discord authentication page.
     */
    public function redirect(): RedirectResponse
    {
        try {
            \Log::info('Discord Redirect Initiated', [
                'session_id' => session()->getId(),
                'config_redirect' => config('services.discord.redirect'),
            ]);
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
            \Log::info('Discord Callback Received', [
                'session_id' => $request->session()->getId(),
                'request_state' => $request->input('state'),
                'session_state' => $request->session()->get('state'), // Note: Socialite pulls it, so we might miss it if we don't peek or if Socialite runs first.
                // Actually Socialite::driver()->user() pulls it.
                'request_code' => $request->input('code'),
            ]);
            
            $discordUser = Socialite::driver('discord')->user();
            
            \Log::info('Discord User Obtained', ['id' => $discordUser->getId()]);
            
        } catch (\Exception $e) {
            \Log::error('Discord Auth Callback Error: ' . $e->getMessage(), [
                'exception' => $e,
                'session_id' => $request->session()->getId(),
                'session_all' => $request->session()->all(),
            ]);
            return redirect(config('app.frontend_url'))->with('error', 'Discord authentication failed.');
        }

        // Invalidate old session and regenerate token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Check Guild Membership
        if (!$this->discordService->isMember($discordUser->getId())) {
             return redirect()->route('login')->with('error', 'Bạn phải là thành viên của Discord server để đăng nhập.');
        }

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
