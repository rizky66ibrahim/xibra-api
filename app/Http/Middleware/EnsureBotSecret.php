<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;

// Background jobs of the bot (not tied to one user): only the shared secret is checked.
class EnsureBotSecret
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('services.telegram.bot_secret');
        $given = (string) $request->header('X-Telegram-Bot-Secret', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return ApiResponse::error('Unauthorized', null, 401);
        }

        return $next($request);
    }
}
