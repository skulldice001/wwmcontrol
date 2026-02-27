<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\InnerWay;
use App\Services\DiscordService;
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
            $response = Socialite::driver('discord')->redirect();
            request()->session()->save();
            return $response;
        } catch (\Exception $e) {
            \Log::error('Discord Redirect Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('home')->with('error', 'Failed to connect to Discord.');
        }
    }

    public function callback(\Illuminate\Http\Request $request, DiscordService $discordService)
    {
        try {
            \Log::info('Discord Callback Received', [
                'session_id' => $request->session()->getId(),
                'request_state' => $request->input('state'),
                'session_state' => $request->session()->get('state'),
                'request_code' => $request->input('code'),
            ]);

            // Workaround for Socialite session state issue
            if ($request->has('state') && !$request->session()->has('state')) {
                $request->session()->put('state', $request->input('state'));
            }

            $discordUser = Socialite::driver('discord')->user();

            \Log::info('Discord User Obtained', ['id' => $discordUser->getId()]);

        } catch (\Exception $e) {
            \Log::error('Discord Auth Callback Error: ' . $e->getMessage(), [
                'exception' => $e,
                'session_id' => $request->session()->getId(),
                'session_all' => $request->session()->all(),
            ]);

             // Temporary debugging: Show error directly to user
             /*
             return response()->json([
                 'message' => 'Discord authentication failed.',
                 'error' => $e->getMessage(),
                 'session_id' => $request->session()->getId(),
                 'session_state' => $request->session()->get('state'),
                 'request_state' => $request->input('state'),
                 'session_data' => $request->session()->all(),
             ], 500);
             */
             return redirect()->route('home')->with('error', 'Discord authentication failed: ' . $e->getMessage());
        }

        // Invalidate old session and regenerate token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $roles = $discordService->getRoles($discordUser->getId());

        if (is_null($roles)) {
             \Log::error('Failed to retrieve roles for Discord user', [
                'discord_id' => $discordUser->getId(),
            ]);
            return redirect()->route('home')->with('error', 'Unable to verify Discord server membership. Please ensure you have joined the required Discord server. If you are already a member, please contact the administrator.');
        }

        if (empty($roles)) {
            \Log::warning('Discord user has no roles in guild', [
                'discord_id' => $discordUser->getId(),
            ]);
            return redirect()->route('home')->with('error', __('messages.discord_guild_required'));
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

        return redirect()->route('dashboard');
    }
}
