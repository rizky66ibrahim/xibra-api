<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ReminderNotifyController extends Controller
{
    // GET: unsent reminders whose time has come, for users with an active Telegram link
    public function due()
    {
        $query = DB::table('reminders')
            ->join('telegram_accounts', 'telegram_accounts.user_id', '=', 'reminders.user_id')
            ->join('users', 'users.id', '=', 'reminders.user_id')
            ->where('telegram_accounts.is_active', true)
            ->where('users.is_active', true)
            ->whereNull('users.deleted_at')
            ->where('reminders.is_sent', false)
            ->where('reminders.reminder_at', '<=', now());

        if (Schema::hasColumn('reminders', 'deleted_at')) {
            $query->whereNull('reminders.deleted_at');
        }

        $rows = $query
            ->orderBy('reminders.reminder_at')
            ->limit(50)
            ->get([
                'reminders.id',
                'reminders.title',
                'reminders.reminder_at',
                'telegram_accounts.telegram_user_id',
            ]);

        return ApiResponse::success(
            $rows->map(fn($r) => [
                'id' => $r->id,
                'title' => $r->title,
                'reminder_at' => Carbon::parse($r->reminder_at)->toIso8601String(),
                'chat_id' => (int) $r->telegram_user_id,
            ])->values(),
            'Due reminders retrieved'
        );
    }

    // POST: the bot reports the outcome. "sent" or "failed" is final, so the reminder is never retried.
    public function markSent(Request $request, Reminder $reminder)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'string', 'in:sent,failed'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $sent = $validator->validated()['status'] === 'sent';

        $reminder->forceFill([
            'is_sent' => true,
            'sent_at' => $sent ? now() : null,
            'delivery_status' => $sent ? 'sent' : 'failed',
        ])->save();

        return ApiResponse::success(null, 'Reminder updated');
    }
}
