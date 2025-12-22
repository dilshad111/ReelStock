<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ReelIssue extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'reel_id',
        'issue_date',
        'quantity_issued',
        'issued_to',
        'remarks',
        'return_to_stock_weight',
        'net_consumed_weight',
        'return_location',
        'auto_return_id',
    ];

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }
}
