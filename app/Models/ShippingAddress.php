<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'address_name', 'full_address', 'contact_person', 'phone'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cartageRates()
    {
        return $this->hasMany(CartageRate::class);
    }
}
