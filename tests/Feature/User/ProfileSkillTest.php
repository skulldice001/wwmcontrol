<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\Skill;
use App\Models\InnerWay;
use Database\Seeders\SkillSeeder;
use Database\Seeders\InnerWaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSkillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed dữ liệu cần thiết cho Skill và Inner Way
        $this->seed(SkillSeeder::class);
        $this->seed(InnerWaySeeder::class);
    }

    public function test_user_can_view_profile(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('profile.edit'));
        $response->assertOk();
    }

    public function test_user_can_update_profile_info(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'ingame_name' => 'NewIngameName',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        
        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@example.com', $user->email);
        $this->assertEquals('NewIngameName', $user->ingame_name);
    }

    public function test_user_can_update_skills_and_inner_ways(): void
    {
        $user = User::factory()->create();
        $mainSkill = Skill::first();
        $subSkill = Skill::skip(1)->first();
        $innerWay = InnerWay::first();

        if (!$mainSkill || !$subSkill || !$innerWay) {
            $this->markTestSkipped('Skills or Inner Ways not seeded correctly.');
        }

        // Test logic: main_skill_id, sub_skill_id, inner_ways levels
        $response = $this->actingAs($user)->put(route('skills.update'), [
            'main_skill_id' => $mainSkill->id,
            'sub_skill_id' => $subSkill->id,
            'inner_ways' => [
                $innerWay->slug => 6 // Max level
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertEquals($mainSkill->id, $user->main_skill_id);
        $this->assertEquals($subSkill->id, $user->sub_skill_id);
        
        // Check pivot table
        $userInnerWay = $user->innerWays()->where('inner_way_id', $innerWay->id)->first();
        $this->assertNotNull($userInnerWay);
        $this->assertEquals(6, $userInnerWay->pivot->level);
    }

    public function test_user_cannot_set_invalid_inner_way_level(): void
    {
        $user = User::factory()->create();
        $innerWay = InnerWay::first();

        if (!$innerWay) {
            $this->markTestSkipped('Inner Ways not seeded correctly.');
        }

        $response = $this->actingAs($user)->put(route('skills.update'), [
            'inner_ways' => [
                $innerWay->slug => 7 // Invalid level (>6)
            ],
        ]);

        $response->assertSessionHasErrors(['inner_ways.' . $innerWay->slug]);
    }
}
