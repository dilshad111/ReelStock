<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngineeringComponentRouting extends Model
{
    use HasFactory;

    protected $fillable = [
        'engineering_component_id',
        'sequence_no',
        'process_name',
        'process_order',
        'is_active',
        'process_instructions',
        'parameters',
    ];

    protected $casts = [
        'sequence_no' => 'integer',
        'process_order' => 'integer',
        'is_active' => 'boolean',
        'parameters' => 'array',
    ];

    public function component()
    {
        return $this->belongsTo(EngineeringProductComponent::class, 'engineering_component_id');
    }
}
