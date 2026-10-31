<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'reminder_at',
        'recurring',
        'recurring_pattern',
        'delivery_status',
        'dedupe_key',
        'is_sent',
        'sent_at',
    ];

    protected $casts = [
        'reminder_at' => 'datetime',
        'sent_at' => 'datetime',
        'recurring' => 'boolean',
        'is_sent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
