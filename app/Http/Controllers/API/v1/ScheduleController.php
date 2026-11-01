<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = Schedule::where('user_id', $request->user()->id)
            ->when($request->filled('start_time'), fn ($q) => $q->whereDate('start_time', '>=', $request->start_time))
            ->when($request->filled('end_time'), fn ($q) => $q->whereDate('end_time', '<=', $request->end_time))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($schedules, 'Schedules retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_all_day' => ['sometimes', 'boolean'],
            'recurring' => ['sometimes', 'boolean'],
            'recurring_pattern' => ['sometimes', 'string', 'in:daily,weekly,monthly'],
            'timezone' => ['sometimes', 'string', 'max:50'],
            'is_overnight' => ['sometimes', 'boolean'],
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'start_time' => ['sometimes', 'date'],
            'end_time' => ['sometimes', 'date'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_all_day' => ['sometimes', 'boolean'],
            'recurring' => ['sometimes', 'boolean'],
            'recurring_pattern' => ['sometimes', 'nullable', 'string', 'in:daily,weekly,monthly'],
            'timezone' => ['sometimes', 'string', 'max:50'],
            'is_overnight' => ['sometimes', 'boolean'],
        ]);

        $validator->after(function ($validator) use ($request, $schedule) {
            if ($validator->errors()->has('start_time') || $validator->errors()->has('end_time')) {
                return;
            }

            if (! $request->hasAny(['start_time', 'end_time'])) {
                return;
            }

            $start = Carbon::parse($request->input('start_time', $schedule->start_time));
            $end = Carbon::parse($request->input('end_time', $schedule->end_time));

            if ($end->lessThanOrEqualTo($start)) {
                $validator->errors()->add('end_time', 'The end time must be after the start time.');
            }
        });

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $data = $validator->validated();
        $schedule->update($data);

        return ApiResponse::success(new ScheduleResource($schedule->fresh()), 'Schedule updated');
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
