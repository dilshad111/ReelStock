<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartageEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'cartage_bill_id',
        'parent_entry_id',
        'entry_date',
        'customer_id',
        'shipping_address_id',
        'vehicle_id',
        'vehicle_number',
        'dc_number',
        'slip_no',
        'amount',
        'is_return',
        'is_second_location',
        'remarks'
    ];

    public function cartageBill()
    {
        return $this->belongsTo(CartageBill::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function parentEntry()
    {
        return $this->belongsTo(CartageEntry::class, 'parent_entry_id');
    }

    public function subEntries()
    {
        return $this->hasMany(CartageEntry::class, 'parent_entry_id');
    }
}
