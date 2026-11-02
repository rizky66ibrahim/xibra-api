<?php

namespace Tests\Feature\Api\v1;

use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'is_active' => true])->save();

        return $admin;
    }

    private function validAccept(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kakak Saya',
            'username' => 'kakak',
            'email' => 'kakak@example.com',
            'phone_number' => '081200000002',
            'password' => 'password-kakak',
            'password_confirmation' => 'password-kakak',
        ], $overrides);
    }

    // Membuat undangan lewat API admin dan mengembalikan token dari link
    private function createInvitation(): string
    {
        Sanctum::actingAs($this->adminUser());

        $response = $this->postJson('/api/v1/admin/invitations', ['label' => 'Kakak']);
        $response->assertCreated();

        return basename($response->json('data.link'));
    }

    public function test_admin_creates_invitation_and_token_is_not_stored_raw(): void
    {
        $token = $this->createInvitation();

        $this->assertDatabaseHas('user_invitations', [
            'token_hash' => UserInvitation::hashToken($token),
        ]);
        $this->assertDatabaseMissing('user_invitations', ['token_hash' => $token]);
    }

    public function test_non_admin_cannot_create_invitation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/admin/invitations', ['label' => 'Kakak'])
            ->assertForbidden();
    }

    public function test_valid_token_can_be_checked(): void
    {
        $token = $this->createInvitation();

        $this->getJson("/api/v1/auth/invitations/{$token}")
            ->assertOk()
            ->assertJsonPath('data.label', 'Kakak');
    }

    public function test_expired_token_is_not_found(): void
    {
        $token = $this->createInvitation();
        UserInvitation::query()->update(['expires_at' => now()->subMinute()]);

        $this->getJson("/api/v1/auth/invitations/{$token}")->assertNotFound();
    }

    public function test_accept_creates_user_and_user_can_login(): void
    {
        $token = $this->createInvitation();

        $this->postJson("/api/v1/auth/invitations/{$token}/accept", $this->validAccept())
            ->assertCreated();

        $kakak = User::where('username', 'kakak')->firstOrFail();
        $this->assertSame('user', $kakak->role);
        $this->assertTrue((bool) $kakak->is_active);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'kakak',
            'password' => 'password-kakak',
        ])->assertOk();
    }

    public function test_invitation_can_only_be_used_once(): void
    {
        $token = $this->createInvitation();

        $this->postJson("/api/v1/auth/invitations/{$token}/accept", $this->validAccept())
            ->assertCreated();

        $this->postJson("/api/v1/auth/invitations/{$token}/accept", $this->validAccept([
            'username' => 'kakak2',
            'email' => 'kakak2@example.com',
            'phone_number' => null,
        ]))->assertNotFound();

        $this->assertDatabaseMissing('users', ['username' => 'kakak2']);
    }

    public function test_accept_rejects_duplicate_username(): void
    {
        User::factory()->create(['username' => 'kakak']);
        $token = $this->createInvitation();

        $this->postJson("/api/v1/auth/invitations/{$token}/accept", $this->validAccept())
            ->assertStatus(422);
    }

    public function test_accept_rejects_password_mismatch(): void
    {
        $token = $this->createInvitation();

        $this->postJson("/api/v1/auth/invitations/{$token}/accept", $this->validAccept([
            'password_confirmation' => 'lain-sekali',
        ]))->assertStatus(422);
    }

    public function test_admin_can_revoke_invitation(): void
    {
        $token = $this->createInvitation();
        $invitation = UserInvitation::firstOrFail();

        Sanctum::actingAs($this->adminUser());
        $this->deleteJson("/api/v1/admin/invitations/{$invitation->id}")->assertOk();

        $this->getJson("/api/v1/auth/invitations/{$token}")->assertNotFound();
    }
}
