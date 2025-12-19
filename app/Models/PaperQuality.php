<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class PaperQuality extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

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
