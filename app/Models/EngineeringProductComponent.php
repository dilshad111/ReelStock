<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngineeringProductComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'engineering_product_id',
        'component_code',
        'component_name',
        'quantity_per_product',
        'component_type',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'quantity_per_product' => 'float',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(EngineeringProduct::class, 'engineering_product_id');
    }

    public function specification()
    {
        return $this->hasOne(EngineeringComponentSpecification::class, 'engineering_component_id');
    }

    public function bomLayers()
    {
        return $this->hasMany(EngineeringComponentBomLayer::class, 'engineering_component_id')->orderBy('layer_sequence');
    }

    public function routings()
    {
        return $this->hasMany(EngineeringComponentRouting::class, 'engineering_component_id')->orderBy('sequence_no');
    }
}
