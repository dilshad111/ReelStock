<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReelReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'reel_id',
        'receiving_date',
        'received_by',
        'gsm',
        'bursting_strength',
        'qc_status',
        'rate_per_kg',
        'remarks',
    ];

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }
}
