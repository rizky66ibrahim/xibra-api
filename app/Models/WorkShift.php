<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkShift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'shift_name',
        'start_time',
        'end_time',
        'break_minutes',
        'work_date',
        'is_active',
        'starts_at',
        'ends_at',
        'shift_code',
        'segments',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
        'break_minutes' => 'integer',
        'work_date' => 'date',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'segments' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
