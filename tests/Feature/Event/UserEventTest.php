<?php

namespace Tests\Feature\Event;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_event_list(): void
    {
        $user = User::factory()->create();
        Event::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('events.index'));

        $response->assertOk();
        $response->assertViewHas('events');
    }

    public function test_user_can_register_for_an_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['status' => 'upcoming', 'type' => 'casual']);

        $response = $this->actingAs($user)->post(route('events.register', $event));

        $response->assertRedirect();
        $this->assertTrue($user->events->contains($event));
    }

    public function test_user_can_unregister_from_an_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['status' => 'upcoming']);
        $user->events()->attach($event);

        $response = $this->actingAs($user)->delete(route('events.unregister', $event));

        $response->assertRedirect();
        $this->assertFalse($user->events->contains($event));
    }

    public function test_user_can_access_event_map_if_placed_in_formation(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'status' => 'upcoming',
            'type' => 'guild_war',
            'formation_data' => [
                'members' => [
                    [
                        'memberId' => $user->id,
                        'x' => 100,
                        'y' => 100
                    ]
                ]
            ]
        ]);
        $user->events()->attach($event);

        $response = $this->actingAs($user)->get(route('events.map', $event));

        $response->assertOk();
    }

    public function test_user_cannot_access_event_map_if_not_placed(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'status' => 'upcoming',
            'type' => 'guild_war',
            'formation_data' => [
                'members' => []
            ]
        ]);
        $user->events()->attach($event);

        $response = $this->actingAs($user)->get(route('events.map', $event));

        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('error');
    }
}
