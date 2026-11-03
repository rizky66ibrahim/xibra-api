<?php

namespace App\Models;

use App\Services\ReminderSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    // Keep automatic reminders in step with the schedule, for every way it is changed
    protected static function booted(): void
    {
        static::saved(fn (Schedule $schedule) => app(ReminderSync::class)->forSchedule($schedule));

        static::deleted(fn (Schedule $schedule) => app(ReminderSync::class)
            ->removeByPrefix("u{$schedule->user_id}:schedule:{$schedule->id}:"));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
