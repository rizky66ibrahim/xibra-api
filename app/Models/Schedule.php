<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'location',
        'is_all_day',
        'recurring',
        'recurring_pattern',
        'timezone',
        'is_overnight',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_all_day' => 'boolean',
        'recurring' => 'boolean',
        'is_overnight' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
