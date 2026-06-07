<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobIssueWastage extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_issue_id',
        'stage_entry_id',
        'stage',
        'wastage_type',
        'quantity',
        'reason',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'float',
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
