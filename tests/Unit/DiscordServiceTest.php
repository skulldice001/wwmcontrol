<?php

namespace Tests\Unit;

use App\Services\DiscordService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordServiceTest extends TestCase
{
    public function test_has_role_returns_true_if_user_has_role(): void
    {
        Config::set('services.discord.bot_token', 'fake-token');
        Config::set('services.discord.guild_id', 'fake-guild-id');
        Config::set('services.discord.role_id', 'role-123');

        Http::fake([
            'https://discord.com/api/v10/guilds/fake-guild-id/members/user-123' => Http::response([
                'roles' => ['role-123', 'role-456']
            ], 200),
        ]);

        $service = new DiscordService();
        $this->assertTrue($service->hasRole('user-123'));
    }

    public function test_has_role_returns_false_if_user_does_not_have_role(): void
    {
        Config::set('services.discord.bot_token', 'fake-token');
        Config::set('services.discord.guild_id', 'fake-guild-id');
        Config::set('services.discord.role_id', 'role-789');

        Http::fake([
            'https://discord.com/api/v10/guilds/fake-guild-id/members/user-123' => Http::response([
                'roles' => ['role-123', 'role-456']
            ], 200),
        ]);

        $service = new DiscordService();
        $this->assertFalse($service->hasRole('user-123'));
    }

    public function test_has_role_returns_false_on_api_error(): void
    {
        Config::set('services.discord.bot_token', 'fake-token');
        Config::set('services.discord.guild_id', 'fake-guild-id');
        Config::set('services.discord.role_id', 'role-123');

        Http::fake([
            'https://discord.com/api/v10/guilds/fake-guild-id/members/user-123' => Http::response([], 404),
        ]);

        $service = new DiscordService();
        $this->assertFalse($service->hasRole('user-123'));
    }

    public function test_has_role_returns_false_if_config_missing(): void
    {
        Config::set('services.discord.bot_token', null);

        $service = new DiscordService();
        $this->assertFalse($service->hasRole('user-123'));
    }

    public function test_get_roles_returns_array_of_roles(): void
    {
        Config::set('services.discord.bot_token', 'fake-token');
        Config::set('services.discord.guild_id', 'fake-guild-id');

        Http::fake([
            'https://discord.com/api/v10/guilds/fake-guild-id/members/user-123' => Http::response([
                'roles' => ['role-123', 'role-456']
            ], 200),
            'https://discord.com/api/v10/guilds/fake-guild-id/roles' => Http::response([
                ['id' => 'role-123', 'name' => 'Staff'],
                ['id' => 'role-456', 'name' => 'Moderator'],
            ], 200),
        ]);

        $service = new DiscordService();
        $roles = $service->getRoles('user-123');

        $this->assertEquals([
            ['id' => 'role-123', 'name' => 'Staff'],
            ['id' => 'role-456', 'name' => 'Moderator'],
        ], $roles);
    }

    public function test_get_roles_returns_empty_array_on_error(): void
    {
        Config::set('services.discord.bot_token', 'fake-token');
        Config::set('services.discord.guild_id', 'fake-guild-id');

        Http::fake([
            'https://discord.com/api/v10/guilds/fake-guild-id/members/user-123' => Http::response([], 404),
        ]);

        $service = new DiscordService();
        $roles = $service->getRoles('user-123');

        $this->assertEquals([], $roles);
    }
}
