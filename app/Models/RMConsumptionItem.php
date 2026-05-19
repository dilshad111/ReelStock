<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMConsumptionItem extends Model
{
    use HasFactory;

    protected $table = 'rm_consumption_items';

    protected $fillable = [
        'rm_consumption_id', 'rm_item_id', 'quantity'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function consumption()
    {
        return $this->belongsTo(RMConsumption::class, 'rm_consumption_id');
    }

    public function item()
    {
        return $this->belongsTo(RMItem::class, 'rm_item_id');
    }
}
