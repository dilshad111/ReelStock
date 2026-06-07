<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FluteFactor extends Model
{
    use HasFactory;

    protected $fillable = [
        'flute_type',
        'factor',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'factor' => 'float',
        'is_active' => 'boolean',
    ];
}
