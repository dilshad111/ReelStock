<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCardChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_card_version_id',
        'field_name',
        'old_value',
        'new_value',
        'modified_by',
        'modified_at',
    ];

    protected $casts = [
        'modified_at' => 'datetime',
    ];

    public function version()
    {
        return $this->belongsTo(JobCardVersion::class, 'job_card_version_id');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
