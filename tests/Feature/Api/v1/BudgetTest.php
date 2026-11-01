<?php

namespace Tests\Feature\Api\v1;

use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_budgets(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Budget::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/budgets');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
    public function test_can_create_budget(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/budgets', [
                'name'      => 'Budget Makanan',
                'category'  => 'makanan',
                'amount'    => 3000000,
                'period'    => 'monthly',
                'start_date' => '2026-11-01',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('budgets', [
            'name'   => 'Budget Makanan',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_budget(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $budget = Budget::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/budgets/' . $budget->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $budget->id]]);
    }

    public function test_cannot_access_other_user_budget(): void
    {
        $owner = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $budget = Budget::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/budgets/' . $budget->id);

        $response->assertStatus(403);
    }

    public function test_can_update_budget(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $budget = Budget::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/budgets/' . $budget->id, [
                'amount' => 5000000,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('budgets', [
            'id'    => $budget->id,
            'amount' => 5000000,
        ]);
    }

    public function test_can_delete_budget(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $budget = Budget::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/budgets/' . $budget->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }
}
