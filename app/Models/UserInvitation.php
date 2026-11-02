<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserInvitation extends Model
{
    protected $fillable = [
        'created_by',
        'label',
        'token_hash',
        'expires_at',
        'used_at',
        'used_by_user_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findUsableByToken(string $token): ?self
    {
        return static::where('token_hash', self::hashToken($token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
