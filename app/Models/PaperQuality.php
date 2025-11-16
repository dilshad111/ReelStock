<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaperQuality extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'quality',
        'item_code',
        'gsm_range',
    ];

    public function reels()
    {
        return $this->hasMany(Reel::class);
    }
}
