<?php

namespace App\Services;

use App\Models\TelegramAccount;
use App\Models\TelegramPairingCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramPairingService
{
    private int $codeLength = 8;

    private int $expiresInMinutes = 5;

    public function generatePairingCode(User $user): array
    {
        // Revoke existing unused codes for this user
        TelegramPairingCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->update(['consumed_at' => now()]);

        // Generate secure random code: XIBRA-XXXX-XXXX
        $rawCode = 'XIBRA-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        $codeHash = Hash::make($rawCode);

        $pairingCode = TelegramPairingCode::create([
            'user_id' => $user->id,
            'code_hash' => $codeHash,
            'expires_at' => now()->addMinutes($this->expiresInMinutes),
        ]);

        Log::info('Pairing code generated', [
            'user_id' => $user->id,
            'code_id' => $pairingCode->id,
            'expires_at' => $pairingCode->expires_at,
        ]);

        return [
            'code' => $rawCode,
            'expires_at' => $pairingCode->expires_at,
        ];
    }

    public function verifyPairingCode(string $rawCode, int $telegramUserId, string $telegramUsername): array
    {
        return DB::transaction(function () use ($rawCode, $telegramUserId, $telegramUsername) {
            // Find all unexpired codes
            $codes = TelegramPairingCode::where('expires_at', '>', now())
                ->whereNull('consumed_at')
                ->lockForUpdate()
                ->get();

            $matchedCode = $codes->first(fn ($code) => Hash::check($rawCode, $code->code_hash));

            if (! $matchedCode) {
                Log::warning('Invalid pairing code attempted', [
                    'telegram_user_id' => $telegramUserId,
                ]);

                return ['success' => false, 'error' => 'Invalid or expired pairing code.'];
            }

            // Check if the code is expired
            $user = $matchedCode->user;

            if (! $user || $user->trashed() || ! $user->is_active) {
                return ['success' => false, 'error' => 'Invalid or expired pairing code.'];
            }
            // Check if Telegram already linked to another user
            $existingTelegram = TelegramAccount::where('telegram_user_id', $telegramUserId)
                ->where('is_active', true)
                ->first();

            if ($existingTelegram && $existingTelegram->user_id !== $user->id) {
                Log::warning('Telegram already linked to another user', [
                    'telegram_user_id' => $telegramUserId,
                    'existing_user_id' => $existingTelegram->user_id,
                    'requested_user_id' => $user->id,
                ]);

                return [
                    'success' => false,
                    'error' => 'Telegram account is already linked to another Xibra account.',
                ];
            }

            $matchedCode->forceFill(['consumed_at' => now()])->save();

            // Check if user already has active Telegram
            $existingUserTelegram = TelegramAccount::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if ($existingUserTelegram) {
                // Update existing
                $existingUserTelegram->update([
                    'telegram_user_id' => $telegramUserId,
                    'telegram_username' => $telegramUsername,
                    'linked_at' => now(),
                    'is_active' => true,
                ]);

                Log::info('Telegram account relinked', [
                    'user_id' => $user->id,
                    'telegram_user_id' => $telegramUserId,
                ]);

                return [
                    'success' => true,
                    'user' => $user,
                    'telegram_account' => $existingUserTelegram,
                    'message' => 'Telegram account relinked successfully.',
                ];
            }

            // Create new link
            $telegramAccount = TelegramAccount::create([
                'user_id' => $user->id,
                'telegram_user_id' => $telegramUserId,
                'telegram_username' => $telegramUsername,
                'linked_at' => now(),
                'is_active' => true,
            ]);

            Log::info('Telegram account linked', [
                'user_id' => $user->id,
                'telegram_user_id' => $telegramUserId,
                'telegram_username' => $telegramUsername,
            ]);

            return [
                'success' => true,
                'user' => $user,
                'telegram_account' => $telegramAccount,
                'message' => 'Telegram account linked successfully.',
            ];
        });
    }

    public function getPairingStatus(User $user): array
    {
        $telegramAccount = TelegramAccount::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $pendingCode = TelegramPairingCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

        return [
            'is_linked' => $telegramAccount !== null,
            'telegram_account' => $telegramAccount,
            'pending_code' => $pendingCode ? [
                'expires_at' => $pendingCode->expires_at,
            ] : null,
        ];
    }

    public function unlinkTelegram(User $user): bool
    {
        $telegramAccount = TelegramAccount::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (! $telegramAccount) {
            return false;
        }

        $telegramAccount->update([
            'is_active' => false,
        ]);

        Log::info('Telegram account unlinked', [
            'user_id' => $user->id,
            'telegram_user_id' => $telegramAccount->telegram_user_id,
        ]);

        return true;
    }
}
