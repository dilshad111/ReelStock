<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconciliationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'run_date',
        'total_reels_checked',
        'discrepancies_found',
        'corrections_made',
        'details',
        'run_by'
    ];

    protected $casts = [
        'run_date' => 'datetime',
        'details' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'run_by');
    }
}
