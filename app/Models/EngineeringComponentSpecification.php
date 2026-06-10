<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngineeringComponentSpecification extends Model
{
    use HasFactory;

    protected $fillable = [
        'engineering_component_id',
        'length',
        'width',
        'height',
        'uom',
        'ply_type',
        'flute_type',
        'board_grade',
        'joint_type',
        'is_printed',
        'printing_colors',
        'printing_color_codes',
        'bundle_quantity',
        'special_instructions',
    ];

    protected $casts = [
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'is_printed' => 'boolean',
        'printing_colors' => 'integer',
        'printing_color_codes' => 'array',
        'bundle_quantity' => 'integer',
    ];

    public function component()
    {
        return $this->belongsTo(EngineeringProductComponent::class, 'engineering_component_id');
    }
}
