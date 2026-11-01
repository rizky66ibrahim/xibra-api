<?php

namespace Tests\Feature\Api\v1;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_accounts(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Account::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/accounts');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_account(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/accounts', [
                'name'     => 'BCA Utama',
                'type'     => 'bank',
                'provider' => 'BCA',
                'balance'  => 2000000,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('accounts', [
            'name'   => 'BCA Utama',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_account(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/accounts/' . $account->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $account->id]]);
    }

    public function test_cannot_access_other_user_account(): void
    {
        $owner = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $account = Account::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/accounts/' . $account->id);

        $response->assertStatus(403);
    }

    public function test_can_update_account(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/accounts/' . $account->id, [
                'balance'  => 5000000,
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('accounts', [
            'id'       => $account->id,
            'balance'  => 5000000,
            'is_active' => false,
        ]);
    }

    public function test_can_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $account = Account::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/accounts/' . $account->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    }
}
