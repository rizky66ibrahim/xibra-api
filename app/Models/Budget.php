<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'amount',
        'period',
        'start_date',
        'end_date',
        'spent_amount',
        'is_active',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'start_date'   => 'date',
        'end_date'     => 'date',
        'is_active'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
