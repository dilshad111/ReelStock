<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCardVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_card_id',
        'version_no',
        'change_request_no',
        'change_reason',
        'effective_date',
        'approval_status',
        'version_status',
        'snapshot_data',
        'created_by',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'effective_date' => 'date',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function changeLogs()
    {
        return $this->hasMany(JobCardChangeLog::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
