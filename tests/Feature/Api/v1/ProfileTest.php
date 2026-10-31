<?php

namespace Tests\Feature\Api\v1;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_not_found_when_not_created(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

        $response->assertStatus(404);
    }

    public function test_can_create_profile(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile', [
                'bio'         => 'Software developer',
                'birth_date'  => '1995-01-01',
                'gender'      => 'male',
                'city'        => 'Jakarta',
                'province'    => 'DKI Jakarta',
                'country'     => 'Indonesia',
                'timezone'    => 'Asia/Jakarta',
                'language'    => 'id',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data', 'message', 'error']);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'city'    => 'Jakarta',
        ]);
    }

    public function test_cannot_create_duplicate_profile(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/profile', [
                'bio' => 'Another bio',
            ]);

        $response->assertStatus(409);
    }

    public function test_can_get_profile(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJson(['data' => ['id' => $profile->id]]);
    }

    public function test_can_update_profile(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'city' => 'Bandung',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('profiles', [
            'id'    => $profile->id,
            'city'  => 'Bandung',
        ]);
    }

    public function test_can_delete_profile(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('profiles', ['id' => $profile->id]);
    }
}
