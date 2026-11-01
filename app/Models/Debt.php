<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'type',
        'amount',
        'currency',
        'description',
        'due_date',
        'paid_amount',
        'status',
        'source_of_funds',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_date'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
