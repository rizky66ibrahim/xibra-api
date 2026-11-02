<?php

namespace Tests\Feature\Api\v1;

use App\Models\Task;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramBridgeTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-bot-secret';
    private const TG_ID = 555001;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram.bot_secret' => self::SECRET]);
    }

    private function linkedUser(): User
    {
        $user = User::factory()->createOne();
        $user->forceFill(['is_active' => true])->save();

        TelegramAccount::create([
            'user_id' => $user->id,
            'telegram_user_id' => self::TG_ID,
            'telegram_username' => 'budi_tg',
            'linked_at' => now(),
            'is_active' => true,
        ]);

        return $user;
    }

    private function botHeaders(?string $secret = self::SECRET, string $telegramUserId = '555001'): array
    {
        $headers = ['X-Telegram-User-Id' => $telegramUserId];

        if ($secret !== null) {
            $headers['X-Telegram-Bot-Secret'] = $secret;
        }

        return $headers;
    }

    public function test_rejects_wrong_bot_secret(): void
    {
        $this->linkedUser();

        $this->withHeaders($this->botHeaders('wrong-secret'))
            ->getJson('/api/v1/integrations/telegram/me')
            ->assertUnauthorized();
    }

    public function test_rejects_missing_bot_secret(): void
    {
        $this->linkedUser();

        $this->withHeaders($this->botHeaders(null))
            ->getJson('/api/v1/integrations/telegram/me')
            ->assertUnauthorized();
    }

    public function test_rejects_invalid_telegram_user_id(): void
    {
        $this->withHeaders($this->botHeaders(self::SECRET, 'abc'))
            ->getJson('/api/v1/integrations/telegram/me')
            ->assertStatus(400);
    }

    public function test_unlinked_telegram_user_is_forbidden(): void
    {
        $this->withHeaders($this->botHeaders(self::SECRET, '999999'))
            ->getJson('/api/v1/integrations/telegram/me')
            ->assertForbidden();
    }

    public function test_me_returns_linked_user(): void
    {
        $user = $this->linkedUser();

        $this->withHeaders($this->botHeaders())
            ->getJson('/api/v1/integrations/telegram/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_lists_only_pending_tasks_of_linked_user(): void
    {
        $user = $this->linkedUser();
        $other = User::factory()->createOne();

        Task::factory()->count(2)->create(['user_id' => $user->id, 'is_completed' => false]);
        Task::factory()->createOne(['user_id' => $user->id, 'is_completed' => true]);
        Task::factory()->createOne(['user_id' => $other->id, 'is_completed' => false]);

        $this->withHeaders($this->botHeaders())
            ->getJson('/api/v1/integrations/telegram/tasks')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_creates_task_for_linked_user(): void
    {
        $user = $this->linkedUser();

        $this->withHeaders($this->botHeaders())
            ->postJson('/api/v1/integrations/telegram/tasks', ['title' => 'Beli susu'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Beli susu');

        $this->assertDatabaseHas('tasks', [
            'user_id' => $user->id,
            'title' => 'Beli susu',
            'status' => 'pending',
        ]);
    }

    public function test_store_requires_title(): void
    {
        $this->linkedUser();

        $this->withHeaders($this->botHeaders())
            ->postJson('/api/v1/integrations/telegram/tasks', [])
            ->assertStatus(422);
    }

    public function test_complete_marks_task_done(): void
    {
        $user = $this->linkedUser();
        $task = Task::factory()->createOne(['user_id' => $user->id, 'is_completed' => false]);

        $this->withHeaders($this->botHeaders())
            ->postJson("/api/v1/integrations/telegram/tasks/{$task->id}/complete")
            ->assertOk();

        $fresh = $task->fresh();
        $this->assertTrue((bool) $fresh->is_completed);
        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_cannot_complete_other_users_task(): void
    {
        $this->linkedUser();
        $other = User::factory()->createOne();
        $task = Task::factory()->createOne(['user_id' => $other->id, 'is_completed' => false]);

        $this->withHeaders($this->botHeaders())
            ->postJson("/api/v1/integrations/telegram/tasks/{$task->id}/complete")
            ->assertNotFound();

        $this->assertFalse((bool) $task->fresh()->is_completed);
    }

    public function test_inactive_user_is_forbidden(): void
    {
        $user = $this->linkedUser();
        $user->forceFill(['is_active' => false])->save();

        $this->withHeaders($this->botHeaders())
            ->getJson('/api/v1/integrations/telegram/me')
            ->assertForbidden();
    }

    public function test_deleted_user_is_forbidden(): void
    {
        $user = $this->linkedUser();
        $user->delete();

        $this->withHeaders($this->botHeaders())
            ->getJson('/api/v1/integrations/telegram/me')
            ->assertForbidden();
    }
}
