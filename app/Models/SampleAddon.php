<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SampleAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'sample_submission_id',
        'type',
        'length',
        'width',
        'height',
        'ply',
        'source',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'width'  => 'decimal:2',
        'height' => 'decimal:2',
    ];

    public function submission()
    {
        return $this->belongsTo(SampleSubmission::class, 'sample_submission_id');
    }

    public function paperLayers()
    {
        return $this->morphMany(SamplePaperLayer::class, 'parent')
                    ->orderBy('layer_sequence');
    }
}
