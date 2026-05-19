<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionLog extends Model
{
    protected $fillable = [
        'job_card_id', 'job_card_step_id', 'date', 'shift', 
        'machine_no', 'quantity', 'wastage', 'operator_name', 
        'remarks', 'created_by'
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function step()
    {
        return $this->belongsTo(JobCardStep::class, 'job_card_step_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
