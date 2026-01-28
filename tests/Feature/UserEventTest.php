<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_see_upcoming_events(): void
    {
        $user = User::factory()->create();
        Event::factory()->create([
            'title' => 'Upcoming Event',
            'status' => 'upcoming',
            'start_time' => now()->addDay()
        ]);
        Event::factory()->create([
            'title' => 'Completed Event',
            'status' => 'completed',
            'start_time' => now()->subDay()
        ]);

        $response = $this->actingAs($user)->getJson('/api/events');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['title' => 'Upcoming Event']);
        $response->assertJsonMissing(['title' => 'Completed Event']);
    }

    public function test_user_can_register_for_casual_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'type' => 'casual',
            'status' => 'upcoming'
        ]);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/register");

        $response->assertStatus(200);
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'preferred_time' => null
        ]);
    }

    public function test_user_must_provide_preferred_time_for_guild_war(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'type' => 'guild_war',
            'status' => 'upcoming'
        ]);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/register", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['preferred_time']);
    }

    public function test_user_can_register_for_guild_war_with_preferred_time(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'type' => 'guild_war',
            'status' => 'upcoming'
        ]);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/register", [
            'preferred_time' => '19:00 - 21:00'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'preferred_time' => '19:00 - 21:00'
        ]);
    }

    public function test_user_can_unregister_from_upcoming_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'status' => 'upcoming'
        ]);
        $event->participants()->attach($user->id);

        $response = $this->actingAs($user)->postJson("/api/events/{$event->id}/unregister");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('event_user', [
            'event_id' => $event->id,
            'user_id' => $user->id
        ]);
    }
}
