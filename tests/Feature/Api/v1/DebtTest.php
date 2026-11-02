<?php

namespace Tests\Feature\Api\v1;

use App\Models\Debt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_debts(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Debt::factory()->count(3)->create(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/debts');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_debt(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/debts', [
                'title'     => 'Utang Budi',
                'type'      => 'debt',
                'amount'    => 500000,
                'status'    => 'pending',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('debts', [
            'title'  => 'Utang Budi',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_debt(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $debt = Debt::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/debts/' . $debt->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $debt->id]]);
    }

    public function test_cannot_access_other_user_debt(): void
    {
        $owner = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $debt = Debt::factory()->createOne(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/debts/' . $debt->id);

        $response->assertStatus(403);
    }

    public function test_can_update_debt(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $debt = Debt::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/debts/' . $debt->id, [
                'paid_amount' => 200000,
                'status'      => 'paid',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('debts', [
            'id'         => $debt->id,
            'paid_amount' => 200000,
            'status'     => 'paid',
        ]);
    }

    public function test_can_delete_debt(): void
    {
        $user = User::factory()->createOne([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $debt = Debt::factory()->createOne(['user_id' => $user->id]);
        /** @var \App\Models\User $user */
        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/debts/' . $debt->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('debts', ['id' => $debt->id]);
    }
}
