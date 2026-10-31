<?php

namespace Tests\Feature\Api\v1;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_schedules(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Schedule::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/schedules');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_schedule(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/schedules', [
                'title'      => 'Weekly Meeting',
                'start_time' => '2026-10-28T14:00:00',
                'end_time'   => '2026-10-28T15:30:00',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('schedules', [
            'title'   => 'Weekly Meeting',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_schedule(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $schedule = Schedule::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/schedules/' . $schedule->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $schedule->id]]);
    }

    public function test_cannot_access_other_user_schedule(): void
    {
        $owner = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $schedule = Schedule::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/schedules/' . $schedule->id);

        $response->assertStatus(403);
    }

    public function test_can_update_schedule(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $schedule = Schedule::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/schedules/' . $schedule->id, [
                'title' => 'Updated Meeting',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('schedules', [
            'id'   => $schedule->id,
            'title' => 'Updated Meeting',
        ]);
    }

    public function test_can_delete_schedule(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $schedule = Schedule::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/schedules/' . $schedule->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('schedules', ['id' => $schedule->id]);
    }
}
