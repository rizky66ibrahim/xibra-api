<?php

namespace Tests\Feature\Api\v1;

use App\Models\Reminder;
use App\Models\Schedule;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramPlannerBridgeTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-bot-secret';
    private const TG_ID = 555002;

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

    private function bot(): array
    {
        return [
            'X-Telegram-Bot-Secret' => self::SECRET,
            'X-Telegram-User-Id' => (string) self::TG_ID,
        ];
    }

    public function test_schedules_index_returns_only_own_schedules_overlapping_today(): void
    {
        $user = $this->linkedUser();
        $other = User::factory()->createOne();

        Schedule::create(['user_id' => $user->id, 'title' => 'Hari ini', 'start_time' => now()->setTime(10, 0), 'end_time' => now()->setTime(11, 0)]);
        Schedule::create(['user_id' => $user->id, 'title' => 'Besok', 'start_time' => now()->addDay()->setTime(10, 0), 'end_time' => now()->addDay()->setTime(11, 0)]);
        Schedule::create(['user_id' => $other->id, 'title' => 'Orang lain', 'start_time' => now()->setTime(12, 0), 'end_time' => now()->setTime(13, 0)]);

        $this->withHeaders($this->bot())
            ->getJson('/api/v1/integrations/telegram/schedules')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Hari ini');
    }

    public function test_schedule_store_creates_schedule(): void
    {
        $user = $this->linkedUser();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/schedules', [
                'title' => 'Meeting',
                'start_time' => now()->addDay()->setTime(9, 0)->toDateTimeString(),
                'end_time' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('schedules', ['user_id' => $user->id, 'title' => 'Meeting']);
    }

    public function test_schedule_store_rejects_end_before_start(): void
    {
        $this->linkedUser();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/schedules', [
                'title' => 'Salah',
                'start_time' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
                'end_time' => now()->addDay()->setTime(9, 0)->toDateTimeString(),
            ])
            ->assertStatus(422);
    }

    public function test_reminders_index_returns_only_upcoming_unsent_own_reminders(): void
    {
        $user = $this->linkedUser();
        $other = User::factory()->createOne();

        Reminder::create(['user_id' => $user->id, 'title' => 'Akan datang', 'reminder_at' => now()->addHour(), 'delivery_status' => 'pending', 'is_sent' => false, 'recurring' => false]);
        Reminder::create(['user_id' => $user->id, 'title' => 'Sudah lewat', 'reminder_at' => now()->subHour(), 'delivery_status' => 'pending', 'is_sent' => false, 'recurring' => false]);
        Reminder::create(['user_id' => $user->id, 'title' => 'Sudah terkirim', 'reminder_at' => now()->addHour(), 'delivery_status' => 'sent', 'is_sent' => true, 'recurring' => false]);
        Reminder::create(['user_id' => $other->id, 'title' => 'Orang lain', 'reminder_at' => now()->addHour(), 'delivery_status' => 'pending', 'is_sent' => false, 'recurring' => false]);

        $this->withHeaders($this->bot())
            ->getJson('/api/v1/integrations/telegram/reminders')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Akan datang');
    }

    public function test_reminder_store_rejects_past_time(): void
    {
        $this->linkedUser();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/reminders', [
                'title' => 'Terlambat',
                'reminder_at' => now()->subHour()->toDateTimeString(),
            ])
            ->assertStatus(422);
    }

    public function test_reminder_store_creates_pending_reminder(): void
    {
        $user = $this->linkedUser();

        $this->withHeaders($this->bot())
            ->postJson('/api/v1/integrations/telegram/reminders', [
                'title' => 'Bayar listrik',
                'reminder_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('reminders', ['user_id' => $user->id, 'title' => 'Bayar listrik', 'is_sent' => false]);
    }

    public function test_shifts_index_returns_own_shifts_for_date(): void
    {
        $user = $this->linkedUser();
        $other = User::factory()->createOne();

        WorkShift::create(['user_id' => $user->id, 'shift_name' => 'Pagi', 'start_time' => '08:00:00', 'end_time' => '17:00:00', 'break_minutes' => 60, 'work_date' => now()->toDateString(), 'is_active' => true]);
        WorkShift::create(['user_id' => $other->id, 'shift_name' => 'Lain', 'start_time' => '08:00:00', 'end_time' => '17:00:00', 'break_minutes' => 60, 'work_date' => now()->toDateString(), 'is_active' => true]);

        $this->withHeaders($this->bot())
            ->getJson('/api/v1/integrations/telegram/shifts')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.shift_name', 'Pagi');
    }
}
