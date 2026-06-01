<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMItemSupplierRate extends Model
{
    use HasFactory;

    protected $table = 'rm_item_supplier_rates';

    protected $fillable = [
        'rm_item_id',
        'supplier_id',
        'rate',
        'effective_from',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'effective_from' => 'date',
        'is_active' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(RMItem::class, 'rm_item_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
