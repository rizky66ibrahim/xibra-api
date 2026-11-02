<?php

namespace App\Http\Controllers\API\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InvitationController extends Controller
{
    private const TTL_HOURS = 72;

    // Admin: daftar undangan (tanpa token)
    public function index()
    {
        $invitations = UserInvitation::latest()->limit(50)->get();

        return ApiResponse::success(
            $invitations->map(fn(UserInvitation $i) => $this->present($i))->values(),
            'Invitations retrieved'
        );
    }

    // Admin: buat undangan. Link hanya dikembalikan sekali di sini, tidak bisa diambil lagi.
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'label' => ['required', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $token = Str::random(48);

        $invitation = UserInvitation::create([
            'created_by' => $request->user()->id,
            'label' => $validator->validated()['label'],
            'token_hash' => UserInvitation::hashToken($token),
            'expires_at' => now()->addHours(self::TTL_HOURS),
        ]);

        return ApiResponse::created([
            'invitation' => $this->present($invitation),
            'link' => rtrim(config('services.frontend.url'), '/') . '/auth/invite/' . $token,
        ], 'Invitation created');
    }

    // Admin: cabut undangan yang belum dipakai
    public function destroy(UserInvitation $invitation)
    {
        $invitation->delete();

        return ApiResponse::success(null, 'Invitation revoked');
    }

    // Publik: cek apakah token masih berlaku (dipakai halaman undangan di web)
    public function show(string $token)
    {
        $invitation = UserInvitation::findUsableByToken($token);

        if (! $invitation) {
            return ApiResponse::error('Invitation not found or expired.', null, 404);
        }

        return ApiResponse::success([
            'label' => $invitation->label,
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ], 'Invitation valid');
    }

    // Publik: kakak membuat akunnya sendiri
    public function accept(Request $request, string $token)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:20', 'unique:users,phone_number'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors()->toArray(), 422);
        }

        $user = DB::transaction(function () use ($token, $validator) {
            // Kunci baris undangan supaya dua permintaan bersamaan tidak sama-sama lolos
            $invitation = UserInvitation::where('token_hash', UserInvitation::hashToken($token))
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $invitation) {
                return null;
            }

            $data = $validator->validated();

            $user = new User;
            $user->forceFill(array_merge($data, [
                'role' => 'user',
                'is_active' => true,
            ]))->save();

            $invitation->forceFill([
                'used_at' => now(),
                'used_by_user_id' => $user->id,
            ])->save();

            return $user;
        });

        if (! $user) {
            return ApiResponse::error('Invitation not found or expired.', null, 404);
        }

        return ApiResponse::created(
            ['username' => $user->username],
            'Account created. You can sign in now.'
        );
    }

    private function present(UserInvitation $invitation): array
    {
        $status = match (true) {
            $invitation->used_at !== null => 'used',
            $invitation->expires_at->isPast() => 'expired',
            default => 'active',
        };

        return [
            'id' => $invitation->id,
            'label' => $invitation->label,
            'status' => $status,
            'expires_at' => $invitation->expires_at->toIso8601String(),
            'used_at' => $invitation->used_at?->toIso8601String(),
        ];
    }
}
