<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SamplePaperLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_type',
        'parent_id',
        'layer_sequence',
        'paper_type',
        'paper_quality_id',
        'gsm',
    ];

    protected $casts = [
        'layer_sequence' => 'integer',
        'paper_quality_id' => 'integer',
        'gsm'            => 'integer',
    ];

    /**
     * Polymorphic parent: SampleSubmission or SampleAddon.
     */
    public function parent()
    {
        return $this->morphTo();
    }

    public function paperQuality()
    {
        return $this->belongsTo(PaperQuality::class, 'paper_quality_id');
    }
}
