<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ReminderBridgeController extends Controller
{
    // Pengingat yang belum terkirim dan belum lewat waktunya
    public function index(Request $request)
    {
        $reminders = Reminder::where('user_id', $request->user()->id)
            ->where('is_sent', false)
            ->where('reminder_at', '>=', now())
            ->orderBy('reminder_at')
            ->limit(20)
            ->get();

        return ApiResponse::success(
            $reminders->map(fn(Reminder $r) => $this->present($r))->values(),
            'Reminders retrieved'
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'reminder_at' => ['required', 'date'],
        ]);

        $validator->after(function ($v) use ($request) {
            if ($v->errors()->has('reminder_at')) {
                return;
            }

            if (Carbon::parse($request->input('reminder_at'))->isPast()) {
                $v->errors()->add('reminder_at', 'Waktu pengingat harus di masa depan.');
            }
        });

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $reminder = Reminder::create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'reminder_at' => $request->input('reminder_at'),
            'recurring' => false,
            'delivery_status' => 'pending',
            'is_sent' => false,
        ]);

        return ApiResponse::created($this->present($reminder), 'Reminder created');
    }

    private function present(Reminder $reminder): array
    {
        return [
            'id' => $reminder->id,
            'title' => $reminder->title,
            'reminder_at' => $reminder->reminder_at->toIso8601String(),
        ];
    }
}
