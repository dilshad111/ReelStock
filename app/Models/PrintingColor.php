<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrintingColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'ink_code',
        'ink_name',
        'created_by',
        'updated_by',
    ];
}
