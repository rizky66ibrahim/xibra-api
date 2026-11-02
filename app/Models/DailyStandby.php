<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DailyStandby extends Model
{

    protected $table = 'daily_standbys';

    protected $fillable = [
        'user_id',
        'work_date',
        'standby_count',
    ];

    protected $casts = [
        'standby_count' => 'integer',
    ];

    protected function workDate(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value === null ? null : Carbon::parse($value)->startOfDay(),
            set: fn($value) => Carbon::parse($value)->toDateString(),
        );
    }
}
