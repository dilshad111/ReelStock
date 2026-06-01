<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class RMItem extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'rm_items';

    protected $fillable = [
        'name', 'code', 'paper_quality_id', 'rm_category_id', 'rm_subcategory_id', 'unit_type',
        'material_type', 'reorder_level', 'minimum_stock', 'maximum_stock',
        'preferred_supplier_id', 'gst_tax_code',
        'cost_price', 'opening_stock', 'min_stock_alert',
        'status', 'remarks'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'opening_stock' => 'decimal:2',
        'min_stock_alert' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'maximum_stock' => 'decimal:2',
    ];

    public function paperQuality()
    {
        return $this->belongsTo(PaperQuality::class, 'paper_quality_id');
    }

    public function category()
    {
        return $this->belongsTo(RMCategory::class, 'rm_category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(RMSubcategory::class, 'rm_subcategory_id');
    }

    public function preferredSupplier()
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(RMStockLedger::class, 'rm_item_id');
    }

    public function latestLedger()
    {
        return $this->hasOne(RMStockLedger::class, 'rm_item_id')->latestOfMany();
    }

    public function supplierRates()
    {
        return $this->hasMany(RMItemSupplierRate::class, 'rm_item_id');
    }
}
