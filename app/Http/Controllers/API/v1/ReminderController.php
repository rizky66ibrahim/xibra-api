<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\ReminderResource;
use App\Helpers\ApiResponse;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $reminders = Reminder::where('user_id', $request->user()->id)
            ->when($request->filled('delivery_status'), fn($q) => $q->where('delivery_status', $request->delivery_status))
            ->when($request->boolean('is_sent'), fn($q) => $q->where('is_sent', true))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($reminders, 'Reminders retrieved');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'             => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'reminder_at'       => ['required', 'date'],
            'recurring'         => ['sometimes', 'boolean'],
            'recurring_pattern' => ['sometimes', 'string', 'in:daily,weekly,monthly'],
            'delivery_status'   => ['sometimes', 'string', 'in:pending,sent,failed'],
            'dedupe_key'        => ['sometimes', 'string', 'max:100', 'unique:reminders'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $reminder = Reminder::create(array_merge(
            $validator->validated(),
            ['user_id' => $request->user()->id]
        ));

        return ApiResponse::created(new ReminderResource($reminder), 'Reminder created');
    }

    public function show(Request $request, Reminder $reminder)
    {
        if ($reminder->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new ReminderResource($reminder), 'Reminder retrieved');
    }

    public function update(Request $request, Reminder $reminder)
    {
        if ($reminder->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'title'             => ['sometimes', 'string', 'max:255'],
            'description'       => ['sometimes', 'string'],
            'reminder_at'       => ['sometimes', 'date'],
            'recurring'         => ['sometimes', 'boolean'],
            'recurring_pattern' => ['sometimes', 'string', 'in:daily,weekly,monthly'],
            'delivery_status'   => ['sometimes', 'string', 'in:pending,sent,failed'],
            'dedupe_key'        => ['sometimes', 'string', 'max:100', 'unique:reminders,dedupe_key,' . $reminder->id],
            'is_sent'           => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        if ($request->boolean('is_sent') && !$reminder->is_sent) {
            $validator->validated()['sent_at'] = now();
        }

        $reminder->update($validator->validated());
        return ApiResponse::success(new ReminderResource($reminder), 'Reminder updated');
    }

    public function destroy(Request $request, Reminder $reminder)
    {
        if ($reminder->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $reminder->delete();
        return ApiResponse::success(null, 'Reminder deleted');
    }
}
