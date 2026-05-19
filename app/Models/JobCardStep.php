<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCardStep extends Model
{
    protected $fillable = [
        'job_card_id', 'step_name', 'sequence', 'status', 
        'produced_qty', 'wastage_qty', 'started_at', 'completed_at'
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function logs()
    {
        return $this->hasMany(ProductionLog::class, 'job_card_step_id');
    }
}
