<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    protected ?string $botToken;
    protected ?string $guildId;

    public function __construct()
    {
        $this->botToken = config('services.discord.bot_token');
        $this->guildId = config('services.discord.guild_id');
    }

    /**
     * Check if a user has a specific role in the configured guild.
     *
     * @param string $discordUserId
     * @param string|null $roleId
     * @return bool
     */
    public function hasRole(string $discordUserId, ?string $roleId = null): bool
    {
        $roles = $this->getRoles($discordUserId);

        if (empty($roles)) {
            return false;
        }

        $roleId = $roleId ?: config('services.discord.role_id');

        if (!$roleId) {
            Log::warning('Discord Role ID is not configured.');
            return false;
        }

        // Check if $roles is an array of IDs or array of objects
        if (!empty($roles) && is_array($roles[0])) {
            return collect($roles)->pluck('id')->contains($roleId);
        }

        return in_array($roleId, $roles);
    }

    /**
     * Check if a user is a member of the configured guild.
     *
     * @param string $discordUserId
     * @return bool
     */
    public function isMember(string $discordUserId): bool
    {
        if (!$this->botToken || !$this->guildId) {
            Log::warning('Discord Bot configuration is incomplete (missing bot_token or guild_id).');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bot {$this->botToken}",
            ])
            ->withOptions([
                'verify' => config('services.discord.guzzle.verify', true),
            ])
            ->get("https://discord.com/api/v10/guilds/{$this->guildId}/members/{$discordUserId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Discord API exception (isMember): {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get all roles for a user in the configured guild.
     *
     * @param string $discordUserId
     * @return array
     */
    public function getRoles(string $discordUserId): array
    {
        if (!$this->botToken || !$this->guildId) {
            Log::warning('Discord Bot configuration is incomplete (missing bot_token or guild_id).');
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bot {$this->botToken}",
            ])
            ->withOptions([
                'verify' => config('services.discord.guzzle.verify', true),
            ])
            ->get("https://discord.com/api/v10/guilds/{$this->guildId}/members/{$discordUserId}");

            if ($response->successful()) {
                $memberData = $response->json();
                $userRoleIds = $memberData['roles'] ?? [];

                // Fetch guild roles to get names
                $guildRoles = $this->getGuildRoles();

                if (empty($guildRoles)) {
                    return $userRoleIds;
                }

                $rolesWithNames = [];
                foreach ($userRoleIds as $roleId) {
                    $rolesWithNames[] = [
                        'id' => $roleId,
                        'name' => $guildRoles[$roleId] ?? 'Unknown Role',
                    ];
                }

                return $rolesWithNames;
            }

            Log::error("Discord API error: {$response->status()} - {$response->body()}");
        } catch (\Exception $e) {
            Log::error("Discord API exception: {$e->getMessage()}");
        }

        return [];
    }

    /**
     * Get all roles in the guild.
     *
     * @return array Map of role_id => role_name
     */
    public function getGuildRoles(): array
    {
        if (!$this->botToken || !$this->guildId) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bot {$this->botToken}",
            ])
            ->withOptions([
                'verify' => config('services.discord.guzzle.verify', true),
            ])
            ->get("https://discord.com/api/v10/guilds/{$this->guildId}/roles");

            if ($response->successful()) {
                $roles = $response->json();
                $rolesMap = [];
                foreach ($roles as $role) {
                    $rolesMap[$role['id']] = $role['name'];
                }
                return $rolesMap;
            }

            Log::error("Discord API error (guild roles): {$response->status()} - {$response->body()}");
        } catch (\Exception $e) {
            Log::error("Discord API exception (guild roles): {$e->getMessage()}");
        }

        return [];
    }
}
