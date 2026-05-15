<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LotSequence extends Model
{
    use HasFactory;

    protected $fillable = ['prefix', 'next_number'];
}
