<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\User;
use App\Services\TelegramPairingService;
use Illuminate\Http\Request;

class TelegramPairingController extends Controller
{
    protected TelegramPairingService $pairingService;

    public function __construct(TelegramPairingService $pairingService)
    {
        $this->pairingService = $pairingService;
    }

    public function generatePairingCode(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->pairingService->generatePairingCode($user);

        return ApiResponse::created([
            'code' => $result['code'],
            'expires_at' => $result['expires_at'],
        ], 'Pairing code generated successfully.');
    }

    public function pairingStatus(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $status = $this->pairingService->getPairingStatus($user);

        return ApiResponse::success($status, 'Pairing status retrieved.');
    }

    public function unlinkTelegram(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $success = $this->pairingService->unlinkTelegram($user);

        if (!$success) {
            return ApiResponse::error('No active Telegram account linked.', null, 404);
        }

        return ApiResponse::success(null, 'Telegram account unlinked successfully.');
    }
}
