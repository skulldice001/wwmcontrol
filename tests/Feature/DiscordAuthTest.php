<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class DiscordAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_discord_redirect(): void
    {
        Socialite::shouldReceive('driver')->with('discord')->andReturn(Mockery::mock('Laravel\Socialite\Contracts\Provider'));
        Socialite::driver('discord')->shouldReceive('redirect')->andReturn(new \Illuminate\Http\RedirectResponse('https://discord.com/api/oauth2/authorize'));

        $response = $this->get(route('auth.discord'));

        $response->assertRedirect();
        $this->assertStringContainsString('discord.com/api/oauth2/authorize', $response->getTargetUrl());
    }

    public function test_discord_callback_creates_user_and_logs_in(): void
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('123456789');
        $abstractUser->shouldReceive('getName')->andReturn('Test User');
        $abstractUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');
        $abstractUser->token = 'fake-token';
        $abstractUser->refreshToken = 'fake-refresh-token';

        Socialite::shouldReceive('driver')->with('discord')->andReturn(Mockery::mock('Laravel\Socialite\Contracts\Provider'));
        Socialite::driver('discord')->shouldReceive('user')->andReturn($abstractUser);

        $response = $this->get('/auth/discord/callback');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('discord_id', '123456789')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
    public function test_discord_callback_works_with_manual_state_injection(): void
    {
        $abstractUser = Mockery::mock('Laravel\Socialite\Two\User');
        $abstractUser->shouldReceive('getId')->andReturn('123456789');
        $abstractUser->shouldReceive('getName')->andReturn('Test User');
        $abstractUser->shouldReceive('getEmail')->andReturn('test@example.com');
        $abstractUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.png');
        $abstractUser->token = 'fake-token';
        $abstractUser->refreshToken = 'fake-refresh-token';

        Socialite::shouldReceive('driver')->with('discord')->andReturn(Mockery::mock('Laravel\Socialite\Contracts\Provider'));
        Socialite::driver('discord')->shouldReceive('user')->andReturn($abstractUser);

        // Simulate request with state but empty session
        $response = $this->withSession([])->get('/auth/discord/callback?state=test-state&code=test-code');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Verify state was injected into session during the request handling
        $this->assertEquals('test-state', session('state'));
    }
}
