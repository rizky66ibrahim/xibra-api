<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\WorkShiftResource;
use App\Helpers\ApiResponse;
use App\Models\WorkShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WorkShiftController extends Controller
{
    public function index(Request $request)
    {
        $shifts = WorkShift::where('user_id', $request->user()->id)
            ->when($request->filled('work_date'), fn($q) => $q->whereDate('work_date', $request->work_date))
            ->when($request->boolean('is_active'), fn($q) => $q->where('is_active', true))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($shifts, 'Work shifts retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift_name'  => ['required', 'string', 'max:100'],
            'start_time'  => ['required', 'date_format:H:i:s'],
            'end_time'    => ['required', 'date_format:H:i:s', 'after:start_time'],
            'break_minutes' => ['sometimes', 'integer', 'min:0'],
            'work_date'   => ['required', 'date'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $shift = WorkShift::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new WorkShiftResource($shift), 'Work shift created');
    }

    public function show(Request $request, WorkShift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new WorkShiftResource($shift), 'Work shift retrieved');
    }

    public function update(Request $request, WorkShift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'shift_name'  => ['sometimes', 'string', 'max:100'],
            'start_time'  => ['sometimes', 'date_format:H:i:s'],
            'end_time'    => ['sometimes', 'date_format:H:i:s', 'after:start_time'],
            'break_minutes' => ['sometimes', 'integer', 'min:0'],
            'work_date'   => ['sometimes', 'date'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $shift->update($validator->validated());
        return ApiResponse::success(new WorkShiftResource($shift), 'Work shift updated');
    }

    public function destroy(Request $request, WorkShift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $shift->delete();
        return ApiResponse::success(null, 'Work shift deleted');
    }
}
