<?php

namespace Tests\Feature\Api\v1;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tasks(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Task::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tasks');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_task(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/tasks', [
                'title' => 'Complete project proposal',
                'priority' => 'high',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Complete project proposal',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_task(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tasks/'.$task->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $task->id]]);
    }

    public function test_cannot_access_other_user_task(): void
    {
        $owner = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $task = Task::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/tasks/'.$task->id);

        $response->assertStatus(403);
    }

    public function test_can_update_task(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/tasks/'.$task->id, [
                'is_completed' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'is_completed' => true,
        ]);
    }

    public function test_can_delete_task(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $task = Task::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/tasks/'.$task->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_completing_task_sets_completed_at(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $task = Task::factory()->create(['user_id' => $user->id, 'is_completed' => false]);

        $this->putJson("/api/v1/tasks/{$task->id}", ['is_completed' => true])->assertOk();

        $this->assertNotNull($task->fresh()->completed_at);
    }
}
