<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReelSequence extends Model
{
    protected $fillable = [
        'prefix',
        'next_number',
    ];

    protected $casts = [
        'next_number' => 'integer',
    ];
}
