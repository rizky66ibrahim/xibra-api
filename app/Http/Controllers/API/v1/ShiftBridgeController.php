<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShiftBridgeController extends Controller
{
    // GET /integrations/telegram/shifts?date=2026-11-03 (default: hari ini)
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), ['date' => ['sometimes', 'date']]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $date = $request->input('date', now()->toDateString());

        $shifts = WorkShift::where('user_id', $request->user()->id)
            ->whereDate('work_date', $date)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        return ApiResponse::success(
            $shifts->map(fn(WorkShift $s) => [
                'id' => $s->id,
                'shift_name' => $s->shift_name,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'break_minutes' => $s->break_minutes,
                'work_date' => $s->work_date->toDateString(),
            ])->values(),
            'Work shifts retrieved'
        );
    }
}
