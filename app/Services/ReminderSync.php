<?php

namespace App\Services;

use App\Models\Reminder;
use App\Models\Schedule;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Creates and removes automatic reminders for schedules, tasks, and night shift blocks.
// Every automatic reminder has a deterministic dedupe_key that starts with the owner and source,
// so it can be replaced or removed safely when the source changes.
class ReminderSync
{
    private const TZ = 'Asia/Jakarta';
    private const TITLE_LIMIT = 250;

    public function forSchedule(Schedule $schedule): void
    {
        $prefix = "u{$schedule->user_id}:schedule:{$schedule->id}:";
        $this->removeByPrefix($prefix);

        $start = Carbon::parse($schedule->start_time);
        $end = Carbon::parse($schedule->end_time);
        $title = $schedule->title;

        $this->create(
            $schedule->user_id,
            $prefix . 'pre10',
            $start->copy()->subMinutes(10),
            '💪 ' . $this->pick('encourage') . " Mulai 10 menit lagi: {$title}"
        );
        $this->create(
            $schedule->user_id,
            $prefix . 'pre5',
            $start->copy()->subMinutes(5),
            "⏳ Mulai 5 menit lagi: {$title}"
        );
        $this->create(
            $schedule->user_id,
            $prefix . 'start',
            $start,
            "▶️ Mulai sekarang: {$title}"
        );
        $this->create(
            $schedule->user_id,
            $prefix . 'end',
            $end,
            $this->pick('rest') . " ({$title} selesai)"
        );
    }

    public function forTask(Task $task): void
    {
        $prefix = "u{$task->user_id}:task:{$task->id}:";
        $this->removeByPrefix($prefix);

        if ($task->is_completed || ! $task->due_date) {
            return;
        }

        // Due date at 08.00 WIB
        $at = Carbon::parse($task->due_date->toDateString() . ' 08:00:00', self::TZ);
        $this->create($task->user_id, $prefix . 'due', $at, "📝 Tenggat hari ini: {$task->title}");
    }

    // One block = H-1 reminder, then for each night: 10 min, 5 min, start, and the rest message at 05.00
    public function forShiftBlock(int $userId, string $startsOn): void
    {
        $prefix = "u{$userId}:block:{$startsOn}:";
        $this->removeByPrefix($prefix);

        $start = Carbon::parse($startsOn, self::TZ)->startOfDay();

        $this->create(
            $userId,
            $prefix . 'h1',
            $start->copy()->subDay()->setTime(16, 0),
            '😌 Besok libur H-1, nikmati harimu.'
        );

        for ($i = 0; $i < (int) config('shift.block_nights'); $i++) {
            $night = $start->copy()->addDays($i)->setTime(20, 0);

            $this->create(
                $userId,
                $prefix . "n{$i}:pre10",
                $night->copy()->subMinutes(10),
                '💪 ' . $this->pick('encourage') . ' Shift malam 10 menit lagi.'
            );
            $this->create(
                $userId,
                $prefix . "n{$i}:pre5",
                $night->copy()->subMinutes(5),
                '⏳ Shift malam 5 menit lagi.'
            );
            $this->create(
                $userId,
                $prefix . "n{$i}:start",
                $night,
                '🌙 Shift malam dimulai sekarang.'
            );
            $this->create(
                $userId,
                $prefix . "n{$i}:end",
                $night->copy()->addHours(9),
                $this->pick('rest') . ' (Shift malam selesai)'
            );
        }
    }

    // Removes every automatic reminder whose key starts with the prefix
    public function removeByPrefix(string $prefix): void
    {
        DB::table('reminders')->where('dedupe_key', 'like', $prefix . '%')->delete();
    }

    // Deletes already-sent reminders older than the given days, to keep the table small
    public function prune(int $days = 30): int
    {
        return DB::table('reminders')
            ->where('is_sent', true)
            ->where('reminder_at', '<', now()->subDays($days))
            ->delete();
    }

    private function create(int $userId, string $key, Carbon $at, string $title): void
    {
        // Past times are never created, so editing a schedule late does not send old alerts
        if ($at->lte(now())) {
            return;
        }

        (new Reminder)->forceFill([
            'user_id' => $userId,
            'title' => mb_substr($title, 0, self::TITLE_LIMIT),
            'reminder_at' => $at,
            'delivery_status' => 'pending',
            'is_sent' => false,
            'recurring' => false,
            'dedupe_key' => $key,
        ])->save();
    }

    private function pick(string $key): string
    {
        $list = config("reminder_messages.{$key}");

        return $list[array_rand($list)];
    }
}
