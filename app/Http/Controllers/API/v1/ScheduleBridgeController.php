<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ScheduleBridgeController extends Controller
{
    // GET /integrations/telegram/schedules?date=2026-11-02 (default: hari ini)
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), ['date' => ['sometimes', 'date']]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $day = Carbon::parse($request->input('date', now()->toDateString()));

        // Jadwal yang beririsan dengan hari itu, termasuk yang melewati tengah malam
        $schedules = Schedule::where('user_id', $request->user()->id)
            ->where('start_time', '<=', $day->copy()->endOfDay())
            ->where('end_time', '>=', $day->copy()->startOfDay())
            ->orderBy('start_time')
            ->limit(50)
            ->get();

        return ApiResponse::success(
            $schedules->map(fn(Schedule $s) => $this->present($s))->values(),
            'Schedules retrieved'
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $schedule = Schedule::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created($this->present($schedule), 'Schedule created');
    }

    private function present(Schedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'location' => $schedule->location,
            'start_time' => $schedule->start_time->toIso8601String(),
            'end_time' => $schedule->end_time->toIso8601String(),
        ];
    }
}
