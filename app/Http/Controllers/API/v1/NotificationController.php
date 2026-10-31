<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\NotificationResource;
use App\Helpers\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->when($request->boolean('is_read'), fn($q) => $q->where('is_read', true))
            ->latest()
            ->paginate(15);

        return ApiResponse::success($notifications, 'Notifications retrieved');
    }

    public function show(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }
        return ApiResponse::success(new NotificationResource($notification), 'Notification retrieved');
    }

    public function update(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'is_read' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        if ($request->boolean('is_read') && !$notification->is_read) {
            $validator->validated()['read_at'] = now();
        }

        $notification->update($validator->validated());
        return ApiResponse::success(new NotificationResource($notification), 'Notification updated');
    }

    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return ApiResponse::error('Forbidden', null, 403);
        }

        $notification->delete();
        return ApiResponse::success(null, 'Notification deleted');
    }
}
