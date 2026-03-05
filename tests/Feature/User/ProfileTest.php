<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Models\Skill;
use App\Models\InnerWay;
use Database\Seeders\SkillSeeder;
use Database\Seeders\InnerWaySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed necessary data
        $this->seed(SkillSeeder::class);
        $this->seed(InnerWaySeeder::class);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Test User Updated',
            'email' => 'test@example.com',
            'ingame_name' => 'InGameName',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user->refresh();

        $this->assertSame('Test User Updated', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('InGameName', $user->ingame_name);
    }

    public function test_skills_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('skills.edit'));

        $response->assertOk();
    }

    public function test_skills_and_inner_ways_can_be_updated(): void
    {
        $user = User::factory()->create();
        $mainSkill = Skill::first();
        $subSkill = Skill::skip(1)->first();
        $innerWay = InnerWay::first();

        // If no skills/inner ways found, skip test or create dummy
        if (!$mainSkill || !$subSkill || !$innerWay) {
             $this->markTestSkipped('Skills or Inner Ways not seeded correctly.');
        }

        $response = $this->actingAs($user)->put(route('skills.update'), [
            'main_skill_id' => $mainSkill->id,
            'sub_skill_id' => $subSkill->id,
            'inner_ways' => [
                $innerWay->slug => 5
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals($mainSkill->id, $user->main_skill_id);
        $this->assertEquals($subSkill->id, $user->sub_skill_id);
        
        $userInnerWay = $user->innerWays()->where('inner_way_id', $innerWay->id)->first();
        $this->assertNotNull($userInnerWay);
        $this->assertEquals(5, $userInnerWay->pivot->level);
    }
}
