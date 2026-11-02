<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use App\Models\TelegramAccount;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureTelegramBot
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('services.telegram.bot_secret');
        $given = (string) $request->header('X-Telegram-Bot-Secret', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return ApiResponse::error('Unauthorized', null, 401);
        }

        $telegramUserId = $request->header('X-Telegram-User-Id');

        if (! is_string($telegramUserId) || ! ctype_digit($telegramUserId)) {
            return ApiResponse::error('Invalid Telegram user id', null, 400);
        }

        $account = TelegramAccount::where('telegram_user_id', (int) $telegramUserId)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return ApiResponse::error('Telegram account is not linked.', null, 403);
        }

        // User with this telegram account must be active
        $user = User::find($account->user_id);

        if (! $user || ! $user->is_active) {
            return ApiResponse::error('Account is not available.', null, 403);
        }

        // Set the user resolver to the authenticated user
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
