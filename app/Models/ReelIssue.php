<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReelIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'reel_id',
        'issue_date',
        'quantity_issued',
        'issued_to',
        'remarks',
    ];

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }
}
