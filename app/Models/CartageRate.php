<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartageRate extends Model
{
    use HasFactory;

    protected $fillable = ['shipping_address_id', 'vehicle_type', 'rate'];

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class);
    }
}
