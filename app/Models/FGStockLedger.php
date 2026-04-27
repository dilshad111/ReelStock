<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FGStockLedger extends Model
{
    use HasFactory;

    protected $table = 'fg_stock_ledger';

    public $timestamps = false;

    protected $fillable = [
        'transaction_type', 'reference_id', 'product_id',
        'customer_id', 'job_number', 'quantity_in', 'quantity_out',
        'balance_after', 'transaction_date'
    ];

    protected $casts = [
        'quantity_in' => 'decimal:2',
        'quantity_out' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
