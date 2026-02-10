<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Reel extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

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

    /**
     * Synchronize balance_weight and status with transaction history
     */
    public function syncBalance()
    {
        // 1. Recalculate Balance
        $totalConsumed = (float) $this->issues()->sum('net_consumed_weight');
        $this->balance_weight = max($this->original_weight - $totalConsumed, 0);

        // 2. Update Status
        $supplierReturn = $this->returns()
            ->where('returned_to', 'supplier')
            ->exists();
        
        if ($supplierReturn) {
            $this->status = 'returned_to_supplier';
        } elseif ($this->balance_weight <= 0) {
            $this->status = 'fully_used';
        } elseif ($this->balance_weight < $this->original_weight) {
            $this->status = 'partially_used';
        } else {
            $this->status = 'in_stock';
        }

        $this->save();
        return $this->balance_weight;
    }
}
