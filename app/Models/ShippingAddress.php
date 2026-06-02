<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'address_name', 'full_address', 'round_trip_distance_km', 'contact_person', 'phone'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function cartageRates()
    {
        return $this->hasMany(CartageRate::class);
    }

    public function cartageEntries()
    {
        return $this->hasMany(CartageEntry::class);
    }

    public function cartageIncrementDetails()
    {
        return $this->hasMany(CartageIncrementDetail::class);
    }

    public function hasTransportHistory()
    {
        return $this->cartageEntries()->exists() || $this->cartageIncrementDetails()->exists();
    }
}
