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
        'name', 'code', 'paper_quality_id', 'unit_type',
        'cost_price', 'opening_stock', 'min_stock_alert',
        'status', 'remarks'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'opening_stock' => 'decimal:2',
        'min_stock_alert' => 'decimal:2',
    ];

    public function paperQuality()
    {
        return $this->belongsTo(PaperQuality::class, 'paper_quality_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(RMStockLedger::class, 'rm_item_id');
    }

    public function latestLedger()
    {
        return $this->hasOne(RMStockLedger::class, 'rm_item_id')->latestOfMany();
    }
}
