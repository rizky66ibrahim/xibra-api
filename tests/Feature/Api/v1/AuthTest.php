<?php

namespace Tests\Feature\Api\v1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_new_user(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'phone_number' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data', 'message', 'error']);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);
    }

    public function test_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'username' => 'testuser2',
            'email' => 'test@example.com',
            'phone_number' => '08123456788',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Validation failed'])
            ->assertJsonStructure(['success', 'data', 'message', 'error']);
    }

    public function test_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'message', 'error'])
            ->assertJson(['success' => true]);
    }

    public function test_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'nonexistent',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_cannot_login_inactive_account(): void
    {
        User::factory()->create([
            'username' => 'inactive',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'inactive',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_can_logout(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_can_get_authenticated_user(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/user');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data', 'message', 'error']);
    }

    public function test_account_locks_after_five_failed_logins(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $user = User::factory()->create([
            'username' => 'budi',
            'password' => 'correct-password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'identifier' => 'budi',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'budi',
            'password' => 'correct-password',
        ])->assertStatus(423);

        $this->assertNotNull($user->fresh()->locked_until);
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        $user = User::factory()->create([
            'username' => 'budi',
            'password' => 'correct-password',
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'identifier' => 'budi',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'budi',
            'password' => 'correct-password',
        ])->assertOk();

        $this->assertSame(0, $user->fresh()->failed_login_attempts);
    }

    public function test_login_by_name_is_rejected(): void
    {
        User::factory()->create([
            'name' => 'Budi Santoso',
            'username' => 'budi',
            'password' => 'correct-password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'Budi Santoso',
            'password' => 'correct-password',
        ])->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create([
            'username' => 'budi',
            'password' => 'correct-password',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'identifier' => 'budi',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'budi',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
