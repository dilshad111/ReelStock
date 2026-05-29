<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartonType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'standard_code',
        'preview_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
