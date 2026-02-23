<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTimeFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_accepts_24h_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'online_from' => '23:30',
                'online_to' => '05:15',
                'country' => 'Vietnam',
                'ingame_name' => 'Tester',
                'ingame_id' => '123',
            ]);

        $response->assertRedirect();
        $user->refresh();

        $this->assertEquals('23:30', $user->online_from);
        $this->assertEquals('05:15', $user->online_to);
    }

    public function test_profile_update_rejects_invalid_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'online_from' => '11:30 PM', // Format sai, nên là H:i
                'online_to' => '05:15 AM',
            ]);

        $response->assertSessionHasErrors(['online_from', 'online_to']);
    }
}
