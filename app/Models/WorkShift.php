<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'start_time'  => 'string',
        'end_time'    => 'string',
        'break_minutes' => 'integer',
        'work_date'   => 'date',
        'is_active'   => 'boolean',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
