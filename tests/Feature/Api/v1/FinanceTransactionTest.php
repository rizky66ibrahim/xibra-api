<?php

namespace Tests\Feature\Api\v1;

use App\Models\FinanceCategory;
use App\Models\FinanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_finance_transactions(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        FinanceTransaction::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/finance/transactions');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_finance_transaction(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $category = FinanceCategory::factory()->create([
            'user_id' => $user->id,
            'type'    => 'expense',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/finance/transactions', [
                'type'                => 'expense',
                'amount'              => 75000,
                'currency'            => 'IDR',
                'description'         => 'Lunch with team',
                'transaction_date'    => '2026-10-27',
                'finance_category_id' => $category->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('finance_transactions', [
            'amount'      => 75000,
            'user_id'     => $user->id,
        ]);
    }

    public function test_can_get_finance_transaction(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $transaction = FinanceTransaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/finance/transactions/' . $transaction->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $transaction->id]]);
    }

    public function test_cannot_access_other_user_finance_transaction(): void
    {
        $owner = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $transaction = FinanceTransaction::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/finance/transactions/' . $transaction->id);

        $response->assertStatus(403);
    }

    public function test_can_update_finance_transaction(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $transaction = FinanceTransaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/finance/transactions/' . $transaction->id, [
                'amount' => 1000000,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('finance_transactions', [
            'id'    => $transaction->id,
            'amount' => 1000000,
        ]);
    }

    public function test_can_delete_finance_transaction(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $transaction = FinanceTransaction::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/finance/transactions/' . $transaction->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('finance_transactions', ['id' => $transaction->id]);
    }
}
