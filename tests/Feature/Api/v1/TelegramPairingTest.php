<?php

namespace Tests\Feature\Api\v1;

use App\Models\TelegramPairingCode;
use App\Models\User;
use App\Services\TelegramPairingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramPairingTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-bot-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.bot_secret' => self::SECRET]);
    }

    private function pair(string $code, int $telegramUserId, ?string $secret = self::SECRET)
    {
        $headers = $secret === null ? [] : ['X-Telegram-Bot-Secret' => $secret];

        return $this->withHeaders($headers)->postJson('/api/v1/integrations/telegram/pair', [
            'code' => $code,
            'telegram_user_id' => $telegramUserId,
            'telegram_username' => 'budi_tg',
        ]);
    }

    private function codeFor(User $user): string
    {
        return app(TelegramPairingService::class)->generatePairingCode($user)['code'];
    }

    public function test_bot_can_pair_with_secret_without_user_token(): void
    {
        $user = User::factory()->create();
        $code = $this->codeFor($user);

        $this->pair($code, 111)->assertOk();

        $this->assertDatabaseHas('telegram_accounts', [
            'user_id' => $user->id,
            'telegram_user_id' => 111,
            'is_active' => true,
        ]);
    }

    public function test_pairing_rejects_wrong_secret(): void
    {
        $user = User::factory()->create();
        $code = $this->codeFor($user);

        $this->pair($code, 111, 'wrong-secret')->assertUnauthorized();

        $this->assertDatabaseMissing('telegram_accounts', ['telegram_user_id' => 111]);
    }

    public function test_pairing_rejects_missing_secret(): void
    {
        $user = User::factory()->create();
        $code = $this->codeFor($user);

        $this->pair($code, 111, null)->assertUnauthorized();
    }

    public function test_code_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $code = $this->codeFor($user);

        $this->pair($code, 111)->assertOk();
        $this->pair($code, 111)->assertStatus(400);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $code = $this->codeFor($user);

        TelegramPairingCode::query()->update(['expires_at' => now()->subMinute()]);

        $this->pair($code, 111)->assertStatus(400);
    }

    public function test_conflict_does_not_burn_the_code(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->pair($this->codeFor($userA), 111)->assertOk();

        $codeB = $this->codeFor($userB);

        $this->pair($codeB, 111)->assertStatus(400);

        $this->assertNull(
            TelegramPairingCode::where('user_id', $userB->id)->firstOrFail()->consumed_at
        );

        $this->pair($codeB, 222)->assertOk();
    }

    public function test_deactivated_user_cannot_pair(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['is_active' => false])->save();

        $code = $this->codeFor($user);

        $this->pair($code, 111)->assertStatus(400);

        $this->assertDatabaseMissing('telegram_accounts', ['user_id' => $user->id]);
    }

    public function test_deleted_user_cannot_pair(): void
    {
        $user = User::factory()->create();
        $code = $this->codeFor($user);

        $user->delete();

        $this->pair($code, 111)->assertStatus(400);

        $this->assertDatabaseMissing('telegram_accounts', ['telegram_user_id' => 111]);
    }
}
