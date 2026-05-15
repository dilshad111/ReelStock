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
        'min_gsm',
        'max_gsm',
        'min_bursting',
        'max_bursting',
        'min_moisture',
        'max_moisture',
        'min_cobb',
        'max_cobb',
        'paper_color',
        'paper_color_id',
    ];

    protected $casts = [
        'min_gsm' => 'decimal:2',
        'max_gsm' => 'decimal:2',
        'min_bursting' => 'decimal:2',
        'max_bursting' => 'decimal:2',
        'min_moisture' => 'decimal:2',
        'max_moisture' => 'decimal:2',
        'min_cobb' => 'decimal:2',
        'max_cobb' => 'decimal:2',
    ];

    public function paperColor()
    {
        return $this->belongsTo(PaperColor::class, 'paper_color_id');
    }

    public function reels()
    {
        return $this->hasMany(Reel::class);
    }
}
