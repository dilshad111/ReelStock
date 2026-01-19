<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'paper_quality_id',
        'reel_size',
        'alert_type',
        'threshold_value',
        'is_active',
    ];

    public function paperQuality()
    {
        return $this->belongsTo(PaperQuality::class);
    }
}
