<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMReceiptItem extends Model
{
    use HasFactory;

    protected $table = 'rm_receipt_items';

    protected $fillable = [
        'rm_receipt_id', 'rm_item_id', 'quantity', 'unit', 'rate', 'total_amount'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function receipt()
    {
        return $this->belongsTo(RMReceipt::class, 'rm_receipt_id');
    }

    public function item()
    {
        return $this->belongsTo(RMItem::class, 'rm_item_id');
    }
}
