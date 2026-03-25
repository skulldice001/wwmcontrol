<?php

namespace Tests\Feature\Event;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_event_list(): void
    {
        $user = User::factory()->create();
        Event::factory()->count(3)->create(['status' => 'upcoming']);

        $response = $this->actingAs($user)->get(route('events.index'));

        $response->assertOk();
        $response->assertViewHas('events');
    }

    public function test_user_can_register_casual_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['status' => 'upcoming', 'type' => 'casual']);

        $response = $this->actingAs($user)->post(route('events.register', $event));

        $response->assertRedirect();
        $this->assertTrue($user->events->contains($event));
    }

    public function test_user_can_register_guild_war_event_with_preferred_time(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['status' => 'upcoming', 'type' => 'guild_war']);

        $response = $this->actingAs($user)->post(route('events.register', $event), [
            'preferred_time' => '19:30',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();
        $this->assertTrue($user->events->contains($event));

        // Check pivot data
        $pivot = $user->events()->where('event_id', $event->id)->first()->pivot;
        $this->assertEquals('19:30', $pivot->preferred_time);
    }

    public function test_user_can_unregister_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['status' => 'upcoming']);
        $user->events()->attach($event);

        $response = $this->actingAs($user)->delete(route('events.unregister', $event));

        $response->assertRedirect();
        $this->assertFalse($user->events->contains($event));
    }

    public function test_user_cannot_access_map_if_not_placed(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['status' => 'upcoming', 'type' => 'guild_war']);
        $user->events()->attach($event);

        $response = $this->actingAs($user)->get(route('events.map', $event));

        // Expect redirect back with error
        $response->assertRedirect(route('events.index'));
        $response->assertSessionHas('error');
    }

    public function test_user_can_access_map_if_placed(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'status' => 'upcoming',
            'type' => 'guild_war',
            'formation_data' => [
                'members' => [
                    [
                        'memberId' => $user->id,
                        'x' => 500,
                        'y' => 500
                    ]
                ]
            ]
        ]);
        $user->events()->attach($event);

        $response = $this->actingAs($user)->get(route('events.map', $event));

        $response->assertOk();
    }
}
