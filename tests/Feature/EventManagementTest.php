<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\User;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_create_event(): void
    {
        $master = Staff::create([
            'name' => 'Master',
            'account' => 'master',
            'email' => 'master@example.com',
            'password' => 'password',
            'role' => Staff::ROLE_MASTER,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($master, 'staff')->postJson('/api/admin/events', [
            'title' => 'Guild War Tournament',
            'type' => 'guild_war',
            'rules' => 'No cheating',
            'rewards' => '1000 Gold',
            'start_time' => now()->addDay()->toDateTimeString(),
            'participant_ids' => [$user->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('events', ['title' => 'Guild War Tournament', 'type' => 'guild_war']);
        $this->assertDatabaseHas('event_user', ['user_id' => $user->id]);
    }

    public function test_admin_can_create_event(): void
    {
        $admin = Staff::create([
            'name' => 'Admin',
            'account' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => Staff::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin, 'staff')->postJson('/api/admin/events', [
            'title' => 'Casual Match',
            'type' => 'casual',
            'start_time' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertStatus(201);
    }

    public function test_observer_cannot_create_event(): void
    {
        $observer = Staff::create([
            'name' => 'Observer',
            'account' => 'observer',
            'email' => 'observer@example.com',
            'password' => 'password',
            'role' => Staff::ROLE_OBSERVER,
        ]);

        $response = $this->actingAs($observer, 'staff')->postJson('/api/admin/events', [
            'title' => 'Forbidden Event',
            'type' => 'casual',
            'start_time' => now()->addDay()->toDateTimeString(),
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_create_duplicate_guild_war_event_in_same_week(): void
    {
        $master = Staff::create([
            'name' => 'Master',
            'account' => 'master',
            'email' => 'master@example.com',
            'password' => 'password',
            'role' => Staff::ROLE_MASTER,
        ]);

        // Create first guild war on a Wednesday
        $firstDate = \Carbon\Carbon::parse('2026-01-28'); // Wednesday
        $this->actingAs($master, 'staff')->postJson('/api/admin/events', [
            'title' => 'First Guild War',
            'type' => 'guild_war',
            'start_time' => $firstDate->toDateTimeString(),
        ])->assertStatus(201);

        // Try to create second guild war on Friday of the same week
        $secondDate = \Carbon\Carbon::parse('2026-01-30'); // Friday
        $response = $this->actingAs($master, 'staff')->postJson('/api/admin/events', [
            'title' => 'Second Guild War',
            'type' => 'guild_war',
            'start_time' => $secondDate->toDateTimeString(),
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Đã có sự kiện Bang chiến trong tuần này.']);
    }
}
