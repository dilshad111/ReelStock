<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptimizationRule extends Model
{
    use HasFactory;

    public const CONDITION_FIELDS = [
        'print_colors',
        'ink_coverage',
        'slot_type',
        'job_type',
        'quantity',
        'quality_priority',
    ];

    public const OPERATORS = ['>', '>=', '=', '<', '<='];

    public const ADJUSTMENT_TYPES = [
        'percentage_reduction',
        'absolute_reduction',
    ];

    protected $fillable = [
        'parameter_name',
        'condition_field',
        'operator',
        'condition_value',
        'adjustment_type',
        'adjustment_value',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'adjustment_value' => 'float',
        'is_active' => 'boolean',
    ];
}
