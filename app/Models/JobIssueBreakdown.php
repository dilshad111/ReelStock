<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobIssueBreakdown extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_issue_id',
        'stage_entry_id',
        'stage',
        'breakdown_start_at',
        'breakdown_end_at',
        'reason',
        'remarks',
    ];

    protected $casts = [
        'breakdown_start_at' => 'datetime',
        'breakdown_end_at' => 'datetime',
    ];

    public function jobIssue()
    {
        return $this->belongsTo(JobIssue::class);
    }

    public function stageEntry()
    {
        return $this->belongsTo(JobIssueStageEntry::class, 'stage_entry_id');
    }
}
