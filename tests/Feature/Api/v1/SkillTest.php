<?php

namespace Tests\Feature\Api\v1;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkillTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_skills(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Skill::factory()->count(3)->create(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/skills');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_skill(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/skills', [
                'name'               => 'PHP',
                'category'           => 'programming',
                'level'              => 'advanced',
                'years_of_experience' => 5,
                'is_public'          => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('skills', [
            'name'  => 'PHP',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_skill(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $skill = Skill::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/skills/' . $skill->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $skill->id]]);
    }

    public function test_cannot_access_other_user_skill(): void
    {
        $owner = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $skill = Skill::factory()->createOne(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/skills/' . $skill->id);

        $response->assertStatus(403);
    }

    public function test_can_update_skill(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $skill = Skill::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/skills/' . $skill->id, [
                'level' => 'expert',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('skills', [
            'id'  => $skill->id,
            'level' => 'expert',
        ]);
    }

    public function test_can_delete_skill(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $skill = Skill::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/skills/' . $skill->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('skills', ['id' => $skill->id]);
    }
}
