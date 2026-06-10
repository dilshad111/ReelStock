<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngineeringComponentBomLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'engineering_component_id',
        'layer_sequence',
        'layer_label',
        'paper_type',
        'paper_quality_id',
        'gsm',
        'supplier_id',
    ];

    protected $casts = [
        'layer_sequence' => 'integer',
        'gsm' => 'integer',
    ];

    public function component()
    {
        return $this->belongsTo(EngineeringProductComponent::class, 'engineering_component_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paperQuality()
    {
        return $this->belongsTo(PaperQuality::class);
    }
}
