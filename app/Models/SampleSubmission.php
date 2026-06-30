<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sample_date',
        'length',
        'width',
        'height',
        'uom',
        'quantity',
        'print_type',
        'ply',
        'size_approval_only',
        'remarks',
        'created_by',
        'sample_made_by',
        'joinery_technique',
    ];

    protected $casts = [
        'sample_date'        => 'date',
        'length'             => 'decimal:2',
        'width'              => 'decimal:2',
        'height'             => 'decimal:2',
        'quantity'           => 'integer',
        'size_approval_only' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paperLayers()
    {
        return $this->morphMany(SamplePaperLayer::class, 'parent')
                    ->orderBy('layer_sequence');
    }

    public function addons()
    {
        return $this->hasMany(SampleAddon::class);
    }
}
