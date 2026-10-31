<?php

namespace Tests\Feature\Api\v1;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_portfolios(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Portfolio::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/portfolios');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_create_portfolio(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/portfolios', [
                'title'       => 'Xibra API',
                'description' => 'Central REST API',
                'is_public'   => true,
                'is_featured' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('portfolios', [
            'title'  => 'Xibra API',
            'user_id' => $user->id,
        ]);
    }

    public function test_can_get_portfolio(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/portfolios/' . $portfolio->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $portfolio->id]]);
    }

    public function test_cannot_access_other_user_portfolio(): void
    {
        $owner = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $attacker = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $portfolio = Portfolio::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($attacker, 'sanctum')
            ->getJson('/api/v1/portfolios/' . $portfolio->id);

        $response->assertStatus(403);
    }

    public function test_can_update_portfolio(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/portfolios/' . $portfolio->id, [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('portfolios', [
            'id'   => $portfolio->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_can_delete_portfolio(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $portfolio = Portfolio::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/portfolios/' . $portfolio->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('portfolios', ['id' => $portfolio->id]);
    }
}
