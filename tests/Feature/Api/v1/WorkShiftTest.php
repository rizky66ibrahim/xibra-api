<?php

namespace Tests\Feature\Api\v1;

use App\Models\WorkShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_work_shifts(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        WorkShift::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/work-shifts');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_work_shift(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/work-shifts', [
                'shift_name'  => 'Morning Shift',
                'start_time'  => '08:00:00',
                'end_time'    => '16:00:00',
                'work_date'   => '2026-10-28',
                'is_active'   => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('work_shifts', [
            'shift_name' => 'Morning Shift',
            'user_id'    => $user->id,
        ]);
    }

    public function test_can_get_work_shift(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $shift = WorkShift::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/work-shifts/' . $shift->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $shift->id]]);
    }

    public function test_cannot_access_other_user_work_shift(): void
    {
        $owner = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $shift = WorkShift::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/work-shifts/' . $shift->id);

        $response->assertStatus(403);
    }

    public function test_can_update_work_shift(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $shift = WorkShift::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/work-shifts/' . $shift->id, [
                'shift_name' => 'Night Shift',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('work_shifts', [
            'id'         => $shift->id,
            'shift_name' => 'Night Shift',
        ]);
    }

    public function test_can_delete_work_shift(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $shift = WorkShift::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/work-shifts/' . $shift->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('work_shifts', ['id' => $shift->id]);
    }
}
