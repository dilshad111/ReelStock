<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'allow_negative_stock', 'status'];

    protected $casts = [
        'allow_negative_stock' => 'boolean',
    ];
}
