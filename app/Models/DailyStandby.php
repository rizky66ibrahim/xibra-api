<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyStandby extends Model
{
    protected $table = 'daily_standbys';

    protected $fillable = [
        'user_id',
        'work_date',
        'standby_count',
    ];

    protected $casts = [
        'work_date' => 'date',
        'standby_count' => 'integer',
    ];
}
