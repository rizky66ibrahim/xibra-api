<?php

namespace App\Services;

use App\Models\FinanceTransaction;
use App\Models\Schedule;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// Data for the two daily check-ins: morning overview (06.00) and evening questions (18.00).
// Only linked, active users are included.
class CheckinService
{
    private const TZ = 'Asia/Jakarta';

    public function __construct(private ShiftRotation $rotation) {}

    public function forSlot(string $slot): array
    {
        $rows = DB::table('telegram_accounts')
            ->join('users', 'users.id', '=', 'telegram_accounts.user_id')
            ->where('telegram_accounts.is_active', true)
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->get([
                'users.id as user_id',
                'users.name',
                'telegram_accounts.telegram_user_id as chat_id',
            ]);

        return $rows->map(function ($row) use ($slot) {
            $userId = (int) $row->user_id;

            $payload = $slot === 'morning' ? $this->morning($userId) : $this->evening($userId);

            return array_merge([
                'chat_id' => (int) $row->chat_id,
                'name' => $row->name,
                'slot' => $slot,
            ], $payload);
        })->values()->all();
    }

    private function morning(int $userId): array
    {
        $today = Carbon::today(self::TZ);

        $shifts = collect($this->rotation->itemsFor($userId, ShiftRotation::day($today->toDateString())))
            ->map(fn ($s) => [
                'name' => $s['shift_name'],
                'start' => $s['start'],
                'end' => $s['end'],
            ])
            ->values()
            ->all();

        $schedules = $this->schedulesOn($userId, $today)
            ->map(fn (Schedule $s) => [
                'title' => $s->title,
                'start' => Carbon::parse($s->start_time)->timezone(self::TZ)->format('H:i'),
                'end' => Carbon::parse($s->end_time)->timezone(self::TZ)->format('H:i'),
            ])
            ->values()
            ->all();

        $open = Task::where('user_id', $userId)->where('is_completed', false)->get(['priority']);

        return [
            'shifts' => $shifts,
            'schedules' => $schedules,
            'tasks_open' => $open->count(),
            'tasks_high' => $open->where('priority', 'high')->count(),
        ];
    }

    private function evening(int $userId): array
    {
        $today = Carbon::today(self::TZ);
        $tomorrow = $today->copy()->addDay();

        $hasTransactions = FinanceTransaction::where('user_id', $userId)
            ->whereDate('transaction_date', $today->toDateString())
            ->exists();

        $hasPlanTomorrow = $this->schedulesOn($userId, $tomorrow)->isNotEmpty()
            || ! empty($this->rotation->itemsFor($userId, ShiftRotation::day($tomorrow->toDateString())));

        return [
            'has_transactions_today' => $hasTransactions,
            'has_plan_tomorrow' => $hasPlanTomorrow,
        ];
    }

    // Schedules that overlap the given day, in WIB
    private function schedulesOn(int $userId, Carbon $day)
    {
        $from = $day->copy()->startOfDay();
        $to = $day->copy()->endOfDay();

        return Schedule::where('user_id', $userId)
            ->where('start_time', '<=', $to)
            ->where('end_time', '>=', $from)
            ->orderBy('start_time')
            ->get();
    }
}
