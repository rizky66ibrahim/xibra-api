<?php

namespace Tests\Feature\Api\v1;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_subscriptions(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Subscription::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/subscriptions');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_subscription(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/subscriptions', [
                'name'          => 'Netflix',
                'provider'      => 'Netflix Inc',
                'amount'        => 54000,
                'billing_cycle' => 'monthly',
                'next_due_date' => '2026-12-01',
                'status'        => 'active',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('subscriptions', [
            'name'   => 'Netflix',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_subscription(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/subscriptions/' . $subscription->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $subscription->id]]);
    }

    public function test_cannot_access_other_user_subscription(): void
    {
        $owner = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/subscriptions/' . $subscription->id);

        $response->assertStatus(403);
    }

    public function test_can_update_subscription(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/subscriptions/' . $subscription->id, [
                'status' => 'cancelled',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('subscriptions', [
            'id'    => $subscription->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_can_delete_subscription(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/subscriptions/' . $subscription->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('subscriptions', ['id' => $subscription->id]);
    }
}
