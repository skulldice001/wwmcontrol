<?php

namespace Tests\Feature\Admin;

use App\Models\Staff;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_event_list(): void
    {
        $admin = Staff::factory()->create(['role' => 'admin']);
        
        $response = $this->actingAs($admin, 'staff')->get(route('admin.events.index'));
        
        $response->assertOk();
    }

    public function test_admin_can_create_event(): void
    {
        $admin = Staff::factory()->create(['role' => 'admin']);

        $eventData = [
            'title' => 'New Event',
            'description' => 'Event Description',
            'type' => 'casual',
            'status' => 'upcoming',
            'start_time' => Carbon::now()->addDays(2)->format('Y-m-d H:i'),
        ];

        $response = $this->actingAs($admin, 'staff')->post(route('admin.events.store'), $eventData);

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', ['title' => 'New Event']);
    }

    public function test_admin_can_update_event(): void
    {
        $admin = Staff::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();

        $updatedData = [
            'title' => 'Updated Event Title',
            'description' => 'Updated Description',
            'type' => 'casual',
            'status' => 'ongoing',
            'start_time' => Carbon::now()->addDays(1)->format('Y-m-d H:i'),
        ];

        $response = $this->actingAs($admin, 'staff')->put(route('admin.events.update', $event), $updatedData);

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseHas('events', ['title' => 'Updated Event Title']);
    }

    public function test_admin_can_delete_event(): void
    {
        $admin = Staff::factory()->create(['role' => 'admin']);
        $event = Event::factory()->create();

        $response = $this->actingAs($admin, 'staff')->delete(route('admin.events.destroy', $event));

        $response->assertRedirect(route('admin.events.index'));
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }
}
