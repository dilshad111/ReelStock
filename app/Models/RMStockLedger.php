<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMStockLedger extends Model
{
    use HasFactory;

    protected $table = 'rm_stock_ledger';

    protected $fillable = [
        'rm_item_id', 'transaction_type', 'reference_id',
        'quantity_in', 'quantity_out', 'balance_after', 'transaction_date'
    ];

    protected $casts = [
        'quantity_in' => 'decimal:2',
        'quantity_out' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function item()
    {
        return $this->belongsTo(RMItem::class, 'rm_item_id');
    }
}
