<?php

namespace App\Models;

use App\Services\ReminderSync;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'scheduled_start_at',
        'scheduled_end_at',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    // A task with a due date gets one reminder on that day. Completing or deleting it removes the reminder.
    protected static function booted(): void
    {
        static::saved(fn (Task $task) => app(ReminderSync::class)->forTask($task));

        static::deleted(fn (Task $task) => app(ReminderSync::class)
            ->removeByPrefix("u{$task->user_id}:task:{$task->id}:"));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
