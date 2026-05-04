<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartageIncrementLog extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_type', 'effective_date', 'increment_type', 'increment_value'];

    public function details()
    {
        return $this->hasMany(CartageIncrementDetail::class);
    }
}
