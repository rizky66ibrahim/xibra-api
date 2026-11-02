<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftBlock extends Model
{
    protected $fillable = [
        'user_id',
        'member',
        'starts_on',
    ];

    protected $casts = [
        'starts_on' => 'date',
    ];
}
