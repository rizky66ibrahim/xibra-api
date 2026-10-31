<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'bio',
        'birth_date',
        'gender',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'website',
        'social_media',
        'timezone',
        'language',
        'avatar_url',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'social_media' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
