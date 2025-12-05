<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReelReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'reel_id',
        'challan_no',
        'vehicle_number',
        'return_to_supplier_id',
        'return_date',
        'remaining_weight',
        'returned_to',
        'condition',
        'remarks',
    ];

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }

    public function returnToSupplier()
    {
        return $this->belongsTo(Supplier::class, 'return_to_supplier_id');
    }
}
