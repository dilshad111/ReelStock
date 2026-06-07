<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobIssueReelConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_issue_id',
        'stage_entry_id',
        'reel_id',
        'reel_no',
        'paper_quality_id',
        'quality_name',
        'layer_label',
        'layer_type',
        'layer_gsm',
        'consumed_weight',
    ];

    protected $casts = [
        'layer_gsm' => 'float',
        'consumed_weight' => 'float',
    ];

    public function jobIssue()
    {
        return $this->belongsTo(JobIssue::class);
    }

    public function stageEntry()
    {
        return $this->belongsTo(JobIssueStageEntry::class, 'stage_entry_id');
    }

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }

    public function paperQuality()
    {
        return $this->belongsTo(PaperQuality::class);
    }
}
