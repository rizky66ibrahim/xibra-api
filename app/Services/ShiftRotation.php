<?php

namespace App\Services;

use App\Models\DailyStandby;
use App\Models\ShiftBlock;
use App\Models\WorkShift;
use Illuminate\Support\Carbon;
use RuntimeException;

class ShiftRotation
{
    public static function day(string $value): Carbon
    {
        return Carbon::parse($value, config('shift.timezone'))->startOfDay();
    }

    // Anggota berikutnya setelah pemegang blok terakhir, mengikuti urutan rotasi
    public function nextMember(?ShiftBlock $last): string
    {
        $rotation = config('shift.rotation');

        if ($last === null) {
            return $rotation[0];
        }

        $index = array_search($last->member, $rotation, true);

        if ($index === false) {
            throw new RuntimeException("Anggota '{$last->member}' tidak ada di urutan rotasi.");
        }

        return $rotation[($index + 1) % count($rotation)];
    }

    // Pratinjau blok malam baru, belum disimpan
    public function preview(int $userId, Carbon $startsOn): array
    {
        $nights = (int) config('shift.block_nights');

        $last = ShiftBlock::where('user_id', $userId)->orderByDesc('starts_on')->first();

        if ($last && $startsOn->lessThanOrEqualTo($last->starts_on)) {
            throw new RuntimeException('Tanggal mulai harus setelah blok malam terakhir.');
        }

        $member = $this->nextMember($last);
        $expected = $last?->starts_on->copy()->addDays($nights);

        return [
            'member' => $member,
            'member_label' => config("shift.members.{$member}", $member),
            'starts_on' => $startsOn->toDateString(),
            'h1_date' => $startsOn->copy()->subDay()->toDateString(),
            'ends_on' => $startsOn->copy()->addDays($nights - 1)->toDateString(),
            'expected_start' => $expected?->toDateString(),
            'gap_warning' => $expected !== null && ! $expected->isSameDay($startsOn),
            'previous' => $last ? [
                'member' => $last->member,
                'starts_on' => $last->starts_on->toDateString(),
                'ends_on' => $last->starts_on->copy()->addDays($nights - 1)->toDateString(),
            ] : null,
        ];
    }

    // Semua shift user pada satu hari: dari blok malam, aturan Jumat, dan catatan manual
    public function itemsFor(int $userId, Carbon $day): array
    {
        $nights = (int) config('shift.block_nights');
        $owner = config('shift.owner_member');
        $dayDate = $day->toDateString();
        $nextDate = $day->copy()->addDay()->toDateString();
        $items = [];
        $onBlockDay = false;

        // Blok yang mungkin mencakup hari ini, atau yang H-1 nya hari ini
        $blocks = ShiftBlock::where('user_id', $userId)
            ->where('member', $owner)
            ->whereDate('starts_on', '>=', $day->copy()->subDays($nights - 1)->toDateString())
            ->whereDate('starts_on', '<=', $nextDate)
            ->get();

        foreach ($blocks as $block) {
            $start = $block->starts_on;

            if ($start->isSameDay($day->copy()->addDay())) {
                $items[] = $this->item('libur', 'Libur (H-1)', null, null, $dayDate, [], 'block');
                $onBlockDay = true;
            }

            if ($day->greaterThanOrEqualTo($start) && $day->lessThanOrEqualTo($start->copy()->addDays($nights - 1))) {
                $items[] = $this->item('3', 'Shift 3', '20:00', '05:00', $nextDate, [
                    ['start' => "{$dayDate} 20:00", 'end' => "{$nextDate} 05:00"],
                ], 'block');
                $onBlockDay = true;
            }
        }

        // Jumat: hanya setelah jumlah standby dijawab, dan tidak bertabrakan dengan blok
        if (! $onBlockDay && $day->dayOfWeekIso === 5) {
            $standby = DailyStandby::where('user_id', $userId)->whereDate('work_date', $dayDate)->first();

            if ($standby?->standby_count === 1) {
                $items[] = $this->item('4', 'Shift 4', '06:00', '02:00', $nextDate, [
                    ['start' => "{$dayDate} 06:00", 'end' => "{$dayDate} 12:00"],
                    ['start' => "{$nextDate} 00:00", 'end' => "{$nextDate} 02:00"],
                ], 'standby');
            } elseif ($standby?->standby_count === 2) {
                $items[] = $this->item('5', 'Shift 5', '14:00', '02:00', $nextDate, [
                    ['start' => "{$dayDate} 14:00", 'end' => "{$nextDate} 02:00"],
                ], 'standby');
            }
        }

        $manual = WorkShift::where('user_id', $userId)
            ->whereDate('work_date', $dayDate)
            ->where('is_active', true)
            ->get();

        foreach ($manual as $row) {
            $items[] = $this->item(
                (string) ($row->shift_code ?? 'manual'),
                $row->shift_name,
                substr($row->start_time, 0, 5),
                substr($row->end_time, 0, 5),
                $dayDate,
                $row->segments ?? [],
                'manual'
            );
        }

        usort($items, fn(array $a, array $b) => strcmp((string) $a['start'], (string) $b['start']));

        return $items;
    }

    private function item(string $code, string $name, ?string $start, ?string $end, string $endsOn, array $segments, string $source): array
    {
        return [
            'source' => $source,
            'code' => $code,
            'shift_name' => $name,
            'start' => $start,
            'end' => $end,
            'ends_on' => $endsOn,
            'segments' => $segments,
        ];
    }
}
