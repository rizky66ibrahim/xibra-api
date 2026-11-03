<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\DailyStandby;
use App\Models\ShiftBlock;
use App\Services\ShiftRotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use App\Services\ReminderSync;

class ShiftPlanBridgeController extends Controller
{
    public function __construct(private ShiftRotation $rotation) {}

    // GET /integrations/telegram/shift-blocks/preview?starts_on=2026-11-17
    public function previewBlock(Request $request)
    {
        $validator = Validator::make($request->all(), ['starts_on' => ['required', 'date']]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        try {
            $preview = $this->rotation->preview(
                $request->user()->id,
                ShiftRotation::day($validator->validated()['starts_on'])
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        return ApiResponse::success($preview, 'Shift block preview');
    }

    // POST /integrations/telegram/shift-blocks: simpan blok setelah konfirmasi di bot
    public function storeBlock(Request $request)
    {
        $validator = Validator::make($request->all(), ['starts_on' => ['required', 'date']]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        try {
            $preview = $this->rotation->preview(
                $request->user()->id,
                ShiftRotation::day($validator->validated()['starts_on'])
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), null, 422);
        }

        // Anggota dihitung ulang di server, tidak diambil dari input bot
        ShiftBlock::create([
            'user_id' => $request->user()->id,
            'member' => $preview['member'],
            'starts_on' => $preview['starts_on'],
        ]);

        app(\App\Services\ReminderSync::class)->forShiftBlock($request->user()->id, $preview['starts_on']);
        return ApiResponse::created($preview, 'Shift block created');
    }

    // GET /integrations/telegram/standby?date=2026-11-06
    public function showStandby(Request $request)
    {
        $validator = Validator::make($request->all(), ['date' => ['required', 'date']]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $day = ShiftRotation::day($validator->validated()['date']);

        $standby = DailyStandby::where('user_id', $request->user()->id)
            ->whereDate('work_date', $day->toDateString())
            ->first();

        return ApiResponse::success(
            $this->standbyPayload($day->toDateString(), $standby?->standby_count),
            'Standby retrieved'
        );
    }

    // POST /integrations/telegram/standby: jawaban jumlah standby untuk hari Jumat
    public function storeStandby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'count' => ['required', 'integer', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $day = ShiftRotation::day($data['date']);

        if ($day->dayOfWeekIso !== 5) {
            return ApiResponse::error('Jumlah standby hanya dicatat untuk hari Jumat.', null, 422);
        }

        $standby = DailyStandby::updateOrCreate(
            ['user_id' => $request->user()->id, 'work_date' => $day->toDateString()],
            ['standby_count' => (int) $data['count']]
        );

        return ApiResponse::success(
            $this->standbyPayload($day->toDateString(), $standby->standby_count),
            'Standby saved'
        );
    }

    private function standbyPayload(string $date, ?int $count): array
    {
        return [
            'date' => $date,
            'is_friday' => ShiftRotation::day($date)->dayOfWeekIso === 5,
            'standby_count' => $count,
        ];
    }
}
