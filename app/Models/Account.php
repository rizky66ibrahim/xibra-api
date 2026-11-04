<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_INVESTMENT = 'investment';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'provider',
        'asset_class',
        'balance',
        'units',
        'unit_price',
        'price_updated_at',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'units' => 'decimal:8',
        'unit_price' => 'decimal:2',
        'price_updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isInvestment(): bool
    {
        return $this->type === self::TYPE_INVESTMENT;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
