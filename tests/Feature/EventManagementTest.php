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
            'title' => 'PVP Tournament',
            'type' => 'pvp',
            'rules' => 'No cheating',
            'rewards' => '1000 Gold',
            'start_time' => now()->addDay()->toDateTimeString(),
            'participant_ids' => [$user->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('events', ['title' => 'PVP Tournament', 'type' => 'pvp']);
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
}
