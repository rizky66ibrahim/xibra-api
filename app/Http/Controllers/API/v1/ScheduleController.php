<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ScheduleResource;
use App\Helpers\ApiResponse;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = Schedule::where('user_id', $request->user()->id)
            ->when($request->filled('start_time'), fn($q) => $q->whereDate('start_time', '>=', $request->start_time))
            ->when($request->filled('end_time'), fn($q) => $q->whereDate('end_time', '<=', $request->end_time))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($schedules, 'Schedules retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'start_time'      => ['required', 'date'],
            'end_time'        => ['required', 'date', 'after:start_time'],
            'location'        => ['nullable', 'string', 'max:255'],
            'is_all_day'      => ['sometimes', 'boolean'],
            'recurring'       => ['sometimes', 'boolean'],
            'recurring_pattern' => ['sometimes', 'string', 'in:daily,weekly,monthly'],
            'timezone'        => ['sometimes', 'string', 'max:50'],
            'is_overnight'    => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $schedule = Schedule::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new ScheduleResource($schedule), 'Schedule created');
    }

    public function show(Request $request, Schedule $schedule)
    {
        if ($schedule->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new ScheduleResource($schedule), 'Schedule retrieved');
    }

    public function update(Request $request, Schedule $schedule)
    {
        if ($schedule->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title'           => ['sometimes', 'string', 'max:255'],
            'description'     => ['sometimes', 'string'],
            'start_time'      => ['sometimes', 'date'],
            'end_time'        => ['sometimes', 'date', 'after:start_time'],
            'location'        => ['sometimes', 'string', 'max:255'],
            'is_all_day'      => ['sometimes', 'boolean'],
            'recurring'       => ['sometimes', 'boolean'],
            'recurring_pattern' => ['sometimes', 'string', 'in:daily,weekly,monthly'],
            'timezone'        => ['sometimes', 'string', 'max:50'],
            'is_overnight'    => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $schedule->update($validator->validated());
        return ApiResponse::success(new ScheduleResource($schedule), 'Schedule updated');
    }

    public function destroy(Request $request, Schedule $schedule)
    {
        if ($schedule->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $schedule->delete();
        return ApiResponse::success(null, 'Schedule deleted');
    }
}
