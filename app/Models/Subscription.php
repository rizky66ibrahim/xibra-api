<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'provider',
        'amount',
        'currency',
        'billing_cycle',
        'next_due_date',
        'status',
        'category',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'next_due_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
