<?php

namespace Tests\Feature\Api\v1;

use App\Models\FinanceTransaction;
use App\Models\Reminder;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\TelegramAccount;
use App\Models\User;
use App\Services\ReminderSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReminderSyncTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::factory()->createOne();
        $user->forceFill(['is_active' => true])->save();

        return $user;
    }

    private function autoReminders(User $user, string $needle)
    {
        return Reminder::where('user_id', $user->id)
            ->where('dedupe_key', 'like', "u{$user->id}:{$needle}%")
            ->get();
    }

    public function test_schedule_creates_pre_start_start_and_end_reminders(): void
    {
        $user = $this->user();
        $start = now()->addDays(3)->setTime(20, 0);

        Schedule::create([
            'user_id' => $user->id,
            'title' => 'Narik Shopee',
            'start_time' => $start,
            'end_time' => $start->copy()->addHours(2),
        ]);

        $this->assertCount(4, $this->autoReminders($user, 'schedule:'));
    }

    public function test_editing_schedule_replaces_its_reminders(): void
    {
        $user = $this->user();
        $schedule = Schedule::create([
            'user_id' => $user->id,
            'title' => 'Narik Shopee',
            'start_time' => now()->addDays(3)->setTime(20, 0),
            'end_time' => now()->addDays(3)->setTime(22, 0),
        ]);

        $newStart = now()->addDays(4)->setTime(9, 0);
        $schedule->update(['start_time' => $newStart, 'end_time' => $newStart->copy()->addHour()]);

        $reminders = $this->autoReminders($user, "schedule:{$schedule->id}:");
        $this->assertCount(4, $reminders);
        $this->assertSame(
            $newStart->copy()->subMinutes(10)->toDateTimeString(),
            $reminders->firstWhere('dedupe_key', "u{$user->id}:schedule:{$schedule->id}:pre10")
                ->reminder_at->toDateTimeString(),
        );
    }

    public function test_deleting_schedule_removes_its_reminders(): void
    {
        $user = $this->user();
        $schedule = Schedule::create([
            'user_id' => $user->id,
            'title' => 'Rapat',
            'start_time' => now()->addDays(2)->setTime(10, 0),
            'end_time' => now()->addDays(2)->setTime(11, 0),
        ]);

        $schedule->delete();

        $this->assertCount(0, $this->autoReminders($user, 'schedule:'));
    }

    public function test_task_with_due_date_gets_one_reminder_at_0800_and_completing_removes_it(): void
    {
        $user = $this->user();
        $task = Task::create([
            'user_id' => $user->id,
            'title' => 'Bayar tagihan',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => now()->addDays(2)->toDateString(),
            'is_completed' => false,
        ]);

        $reminders = $this->autoReminders($user, "task:{$task->id}:");
        $this->assertCount(1, $reminders);
        $this->assertSame('08:00:00', $reminders->first()->reminder_at->format('H:i:s'));

        $task->update(['is_completed' => true, 'completed_at' => now()]);

        $this->assertCount(0, $this->autoReminders($user, "task:{$task->id}:"));
    }

    public function test_night_shift_block_creates_h1_and_four_reminders_per_night(): void
    {
        $user = $this->user();

        app(ReminderSync::class)->forShiftBlock($user->id, now()->addDays(10)->toDateString());

        $this->assertCount(1 + 4 * (int) config('shift.block_nights'), $this->autoReminders($user, 'block:'));
    }

    public function test_prune_deletes_old_sent_reminders_only(): void
    {
        $user = $this->user();

        $old = Reminder::create(['user_id' => $user->id, 'title' => 'Lama', 'reminder_at' => now()->subDays(40), 'delivery_status' => 'sent', 'is_sent' => true, 'recurring' => false]);
        $recent = Reminder::create(['user_id' => $user->id, 'title' => 'Baru', 'reminder_at' => now()->subDays(2), 'delivery_status' => 'sent', 'is_sent' => true, 'recurring' => false]);
        $unsent = Reminder::create(['user_id' => $user->id, 'title' => 'Belum', 'reminder_at' => now()->subDays(40), 'delivery_status' => 'pending', 'is_sent' => false, 'recurring' => false]);

        app(ReminderSync::class)->prune(30);

        $this->assertNull(Reminder::find($old->id));
        $this->assertNotNull(Reminder::find($recent->id));
        $this->assertNotNull(Reminder::find($unsent->id));
    }

    public function test_checkin_evening_reports_missing_transactions_and_plan(): void
    {
        $user = $this->user();
        $secret = 'test-bot-secret';
        config(['services.telegram.bot_secret' => $secret]);

        TelegramAccount::create([
            'user_id' => $user->id,
            'telegram_user_id' => 555301,
            'telegram_username' => 'owner',
            'linked_at' => now(),
            'is_active' => true,
        ]);

        $this->withHeaders(['X-Telegram-Bot-Secret' => $secret])
            ->getJson('/api/v1/integrations/telegram/checkins/due?slot=evening')
            ->assertOk()
            ->assertJsonPath('data.0.chat_id', 555301)
            ->assertJsonPath('data.0.has_transactions_today', false)
            ->assertJsonPath('data.0.has_plan_tomorrow', false);
    }
}
