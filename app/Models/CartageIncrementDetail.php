<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartageIncrementDetail extends Model
{
    use HasFactory;

    protected $fillable = ['cartage_increment_log_id', 'shipping_address_id', 'old_rate', 'new_rate', 'amount_increase'];

    public function log()
    {
        return $this->belongsTo(CartageIncrementLog::class, 'cartage_increment_log_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class);
    }
}
