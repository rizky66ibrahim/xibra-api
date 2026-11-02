<?php

namespace Tests\Feature\Api\v1;

use App\Models\FinanceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_finance_categories(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        FinanceCategory::factory()->count(3)->create(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/finance/categories');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_finance_category(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/finance/categories', [
                'name'  => 'Makanan',
                'type'  => 'expense',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('finance_categories', [
            'name'  => 'Makanan',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_finance_category(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/finance/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $category->id]]);
    }

    public function test_cannot_access_other_user_finance_category(): void
    {
        $owner = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()->createOne(['user_id' => $owner->id]);

        /** @var \App\Models\User $attacker */
        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/finance/categories/' . $category->id);

        $response->assertStatus(403);
    }

    public function test_can_update_finance_category(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/finance/categories/' . $category->id, [
                'name' => 'Makanan & Minuman',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('finance_categories', [
            'id'  => $category->id,
            'name' => 'Makanan & Minuman',
        ]);
    }

    public function test_can_delete_finance_category(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/finance/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('finance_categories', ['id' => $category->id]);
    }
}
