<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DiscordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DiscordRoleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_role_endpoint_returns_has_role_status(): void
    {
        $user = User::factory()->create([
            'discord_id' => '123456789',
        ]);

        Sanctum::actingAs($user);

        // Mock Discord API response via DiscordService internals or Http fake
        config(['services.discord.bot_token' => 'fake-token']);
        config(['services.discord.guild_id' => 'fake-guild-id']);
        config(['services.discord.role_id' => 'role-123']);

        Http::fake([
            'https://discord.com/api/v10/guilds/fake-guild-id/members/123456789' => Http::response([
                'roles' => ['role-123']
            ], 200),
        ]);

        $response = $this->getJson('/api/discord/check-role');

        $response->assertStatus(200)
            ->assertJson(['has_role' => true]);
    }

    public function test_check_role_endpoint_returns_error_if_no_discord_id(): void
    {
        $user = User::factory()->create([
            'discord_id' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/discord/check-role');

        $response->assertStatus(400)
            ->assertJson(['error' => 'User does not have a connected Discord account.']);
    }

    public function test_check_role_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/discord/check-role');

        $response->assertStatus(401);
    }

    public function test_get_roles_endpoint_returns_roles(): void
    {
        $user = User::factory()->create([
            'discord_id' => '123456789',
        ]);

        Sanctum::actingAs($user);

        config(['services.discord.bot_token' => 'fake-token']);
        config(['services.discord.guild_id' => 'fake-guild-id']);

        Http::fake([
            'https://discord.com/api/v10/guilds/fake-guild-id/members/123456789' => Http::response([
                'roles' => ['role-1', 'role-2']
            ], 200),
        ]);

        $response = $this->getJson('/api/discord/roles');

        $response->assertStatus(200)
            ->assertJson(['roles' => ['role-1', 'role-2']]);
    }
}
