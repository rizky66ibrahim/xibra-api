<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\WorkShift;
use App\Services\ShiftRotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftBridgeController extends Controller
{
    public function __construct(private ShiftRotation $rotation) {}

    // GET /integrations/telegram/shifts?date=... (default: hari ini)
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), ['date' => ['sometimes', 'date']]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $day = ShiftRotation::day($request->input('date', now()->toDateString()));

        return ApiResponse::success(
            $this->rotation->itemsFor($request->user()->id, $day),
            'Work shifts retrieved'
        );
    }

    // POST /integrations/telegram/shifts: catat Shift 1 atau 2 di luar blok malam
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'date'],
            'shift_code' => ['required', 'string', 'in:1,2'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $day = ShiftRotation::day($data['date']);
        $code = $data['shift_code'];
        $userId = $request->user()->id;

        $exists = WorkShift::where('user_id', $userId)
            ->whereDate('work_date', $day->toDateString())
            ->where('shift_code', $code)
            ->exists();

        if ($exists) {
            return ApiResponse::error('Shift ini sudah tercatat untuk tanggal tersebut.', null, 422);
        }

        $manual = config("shift.manual_codes.{$code}");

        $shift = WorkShift::create([
            'user_id' => $userId,
            'shift_name' => $manual['name'],
            'shift_code' => $code,
            'start_time' => $manual['start'],
            'end_time' => $manual['end'],
            'break_minutes' => 0,
            'work_date' => $day->toDateString(),
            'starts_at' => $day->copy()->setTimeFromTimeString($manual['start']),
            'ends_at' => $day->copy()->setTimeFromTimeString($manual['end']),
            'is_active' => true,
        ]);

        return ApiResponse::created([
            'id' => $shift->id,
            'code' => $code,
            'shift_name' => $shift->shift_name,
            'date' => $day->toDateString(),
        ], 'Shift recorded');
    }
}
