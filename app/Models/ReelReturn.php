<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReelReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'reel_id',
        'return_date',
        'remaining_weight',
        'condition',
        'remarks',
    ];

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }
}
