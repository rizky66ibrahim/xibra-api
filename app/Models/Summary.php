<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Summary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'period',
        'start_date',
        'end_date',
        'total_income',
        'total_expense',
        'net',
        'net_worth',
        'breakdown',
    ];

    protected $casts = [
        'total_income' => 'decimal:2',
        'total_expense' => 'decimal:2',
        'net'          => 'decimal:2',
        'net_worth'    => 'decimal:2',
        'breakdown'    => 'array',
        'start_date'   => 'date',
        'end_date'     => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
