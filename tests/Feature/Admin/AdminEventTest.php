<?php

namespace Tests\Feature\Admin;

use App\Models\Staff;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard(): void
    {
        $admin = Staff::factory()->create();

        $response = $this->actingAs($admin, 'staff')->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_admin_can_view_event_list(): void
    {
        $admin = Staff::factory()->create();
        Event::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'staff')->get(route('admin.events.index'));

        $response->assertOk();
    }

    public function test_admin_can_create_event(): void
    {
        $admin = Staff::factory()->create();

        $eventData = [
            'title' => 'New Event',
            'description' => 'Event Description',
            'type' => 'casual',
            'status' => 'upcoming',
            'start_time' => now()->addDays(2)->format('Y-m-d\TH:i'),
        ];

        $response = $this->actingAs($admin, 'staff')->post(route('admin.events.store'), $eventData);

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', ['title' => 'New Event']);
    }

    public function test_admin_can_update_event(): void
    {
        $admin = Staff::factory()->create();
        $event = Event::factory()->create();

        $updatedData = [
            'title' => 'Updated Event Title',
            'description' => 'Updated Description',
            'type' => 'casual',
            'status' => 'ongoing',
            'start_time' => now()->addDays(1)->format('Y-m-d H:i:s'),
        ];

        $response = $this->actingAs($admin, 'staff')->put(route('admin.events.update', $event), $updatedData);

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', ['title' => 'Updated Event Title']);
    }
}
