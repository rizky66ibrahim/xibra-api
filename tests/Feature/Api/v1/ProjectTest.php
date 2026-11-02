<?php

namespace Tests\Feature\Api\v1;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_projects(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Project::factory()->count(3)->create(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_project(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/projects', [
                'title'       => 'Xibra API',
                'description' => 'Central REST API',
                'is_featured' => true,
                'is_public'   => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('projects', [
            'title'  => 'Xibra API',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_project(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $project = Project::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/projects/' . $project->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $project->id]]);
    }

    public function test_cannot_access_other_user_project(): void
    {
        $owner = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $project = Project::factory()->createOne(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/projects/' . $project->id);

        $response->assertStatus(403);
    }

    public function test_can_update_project(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $project = Project::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/projects/' . $project->id, [
                'title' => 'Updated Project',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('projects', [
            'id'   => $project->id,
            'title' => 'Updated Project',
        ]);
    }

    public function test_can_delete_project(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $project = Project::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/projects/' . $project->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
