<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\User;
use App\Services\TelegramPairingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramPairingIntegrationController extends Controller
{
    protected TelegramPairingService $pairingService;

    public function __construct(TelegramPairingService $pairingService)
    {
        $this->pairingService = $pairingService;
    }

    public function pair(Request $request)
    {
        // Verify bot authentication
        $botSecret = $request->header('X-Telegram-Bot-Secret');
        $expectedSecret = config('services.telegram.bot_secret');

        if (!$botSecret || !$expectedSecret || !hash_equals($botSecret, $expectedSecret)) {
            Log::warning('Unauthorized bot pairing attempt', [
                'ip' => $request->ip(),
            ]);

            return ApiResponse::error('Unauthorized.', null, 401);
        }

        $request->validate([
            'code' => ['required', 'string'],
            'telegram_user_id' => ['required', 'integer'],
            'telegram_username' => ['sometimes', 'nullable', 'string'],
        ]);

        $result = $this->pairingService->verifyPairingCode(
            $request->string('code'),
            $request->integer('telegram_user_id'),
            $request->string('telegram_username') ?? ''
        );

        if (!$result['success']) {
            return ApiResponse::error($result['error'], null, 400);
        }

        return ApiResponse::success([
            'user_id' => $result['user']->id,
            'username' => $result['user']->username,
            'email' => $result['user']->email,
            'telegram_account' => $result['telegram_account'],
        ], $result['message']);
    }
}
