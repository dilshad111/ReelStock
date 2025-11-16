<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reel extends Model
{
    use HasFactory;

    protected $fillable = [
        'reel_no',
        'paper_quality_id',
        'supplier_id',
        'reel_size',
        'original_weight',
        'balance_weight',
        'status',
    ];

    public function paperQuality()
    {
        return $this->belongsTo(PaperQuality::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receipts()
    {
        return $this->hasMany(ReelReceipt::class);
    }

    public function issues()
    {
        return $this->hasMany(ReelIssue::class);
    }

    public function returns()
    {
        return $this->hasMany(ReelReturn::class);
    }
}
