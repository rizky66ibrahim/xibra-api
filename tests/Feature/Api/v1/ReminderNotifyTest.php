<?php

namespace Tests\Feature\Api\v1;

use App\Models\Reminder;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderNotifyTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-bot-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.telegram.bot_secret' => self::SECRET]);
    }

    private function secret(): array
    {
        return ['X-Telegram-Bot-Secret' => self::SECRET];
    }

    private function linkedUser(int $telegramId): User
    {
        $user = User::factory()->createOne();
        $user->forceFill(['is_active' => true])->save();

        TelegramAccount::create([
            'user_id' => $user->id,
            'telegram_user_id' => $telegramId,
            'telegram_username' => 'user_tg',
            'linked_at' => now(),
            'is_active' => true,
        ]);

        return $user;
    }

    private function reminder(User $user, string $at, bool $sent = false): Reminder
    {
        return Reminder::create([
            'user_id' => $user->id,
            'title' => 'Ingat',
            'reminder_at' => $at,
            'delivery_status' => 'pending',
            'is_sent' => $sent,
            'recurring' => false,
        ]);
    }

    public function test_due_returns_only_unsent_past_reminders_of_linked_users(): void
    {
        $linked = $this->linkedUser(555101);
        $unlinked = User::factory()->createOne();

        $due = $this->reminder($linked, now()->subMinute()->toDateTimeString());
        $this->reminder($linked, now()->addHour()->toDateTimeString());
        $this->reminder($linked, now()->subMinute()->toDateTimeString(), true);
        $this->reminder($unlinked, now()->subMinute()->toDateTimeString());

        $this->withHeaders($this->secret())
            ->getJson('/api/v1/integrations/telegram/reminders/due')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $due->id)
            ->assertJsonPath('data.0.chat_id', 555101);
    }

    public function test_wrong_secret_is_rejected(): void
    {
        $this->withHeaders(['X-Telegram-Bot-Secret' => 'salah'])
            ->getJson('/api/v1/integrations/telegram/reminders/due')
            ->assertUnauthorized();
    }

    public function test_marking_sent_removes_reminder_from_due_list(): void
    {
        $user = $this->linkedUser(555102);
        $reminder = $this->reminder($user, now()->subMinute()->toDateTimeString());

        $this->withHeaders($this->secret())
            ->postJson("/api/v1/integrations/telegram/reminders/{$reminder->id}/sent", ['status' => 'sent'])
            ->assertOk();

        $fresh = $reminder->fresh();
        $this->assertTrue((bool) $fresh->is_sent);
        $this->assertSame('sent', $fresh->delivery_status);
        $this->assertNotNull($fresh->sent_at);

        $this->withHeaders($this->secret())
            ->getJson('/api/v1/integrations/telegram/reminders/due')
            ->assertJsonCount(0, 'data');
    }

    public function test_failed_status_is_terminal_and_has_no_sent_time(): void
    {
        $user = $this->linkedUser(555103);
        $reminder = $this->reminder($user, now()->subMinute()->toDateTimeString());

        $this->withHeaders($this->secret())
            ->postJson("/api/v1/integrations/telegram/reminders/{$reminder->id}/sent", ['status' => 'failed'])
            ->assertOk();

        $fresh = $reminder->fresh();
        $this->assertTrue((bool) $fresh->is_sent);
        $this->assertSame('failed', $fresh->delivery_status);
        $this->assertNull($fresh->sent_at);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $user = $this->linkedUser(555104);
        $reminder = $this->reminder($user, now()->subMinute()->toDateTimeString());

        $this->withHeaders($this->secret())
            ->postJson("/api/v1/integrations/telegram/reminders/{$reminder->id}/sent", ['status' => 'entah'])
            ->assertStatus(422);
    }
}
