<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobIssueStageEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_issue_id',
        'stage',
        'machine_id',
        'machine_name',
        'operator_id',
        'operator_name',
        'start_at',
        'end_at',
        'quantity_produced',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'quantity_produced' => 'float',
    ];

    public function jobIssue()
    {
        return $this->belongsTo(JobIssue::class);
    }

    public function machine()
    {
        return $this->belongsTo(ProductionMachine::class);
    }

    public function operator()
    {
        return $this->belongsTo(MachineOperator::class, 'operator_id');
    }

    public function breakdowns()
    {
        return $this->hasMany(JobIssueBreakdown::class, 'stage_entry_id');
    }

    public function wastages()
    {
        return $this->hasMany(JobIssueWastage::class, 'stage_entry_id');
    }

    public function reelConsumptions()
    {
        return $this->hasMany(JobIssueReelConsumption::class, 'stage_entry_id');
    }
}
