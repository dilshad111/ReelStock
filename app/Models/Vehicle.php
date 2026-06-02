<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = ['vehicle_number', 'vehicle_type', 'mileage', 'transporter_id'];

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }
}
