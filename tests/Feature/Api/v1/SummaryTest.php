<?php

namespace Tests\Feature\Api\v1;

use App\Models\Summary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_summaries(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Summary::factory()->count(3)->create(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/summaries');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_summary(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/summaries', [
                'period'      => 'monthly',
                'start_date'  => '2026-11-01',
                'end_date'    => '2026-11-30',
                'total_income' => 5000000,
                'total_expense' => 2000000,
                'net'         => 3000000,
                'net_worth'   => 10000000,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('summaries', [
            'period'     => 'monthly',
            'user_id'    => $user->id,
        ]);
    }

    public function test_can_get_summary(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $summary = Summary::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/summaries/' . $summary->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $summary->id]]);
    }

    public function test_cannot_access_other_user_summary(): void
    {
        $owner = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $summary = Summary::factory()->createOne(['user_id' => $owner->id]);

        /** @var \App\Models\User $attacker */
        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/summaries/' . $summary->id);

        $response->assertStatus(403);
    }

    public function test_can_update_summary(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $summary = Summary::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/summaries/' . $summary->id, [
                'net_worth' => 15000000,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('summaries', [
            'id'      => $summary->id,
            'net_worth' => 15000000,
        ]);
    }

    public function test_can_delete_summary(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $summary = Summary::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/summaries/' . $summary->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('summaries', ['id' => $summary->id]);
    }
}
