<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobIssue extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_no',
        'job_card_id',
        'customer_id',
        'product_id',
        'purchase_order_no',
        'required_carton_qty',
        'issued_date',
        'production_route',
        'current_stage',
        'status',
        'final_finished_qty',
        'rejected_cartons_qty',
        'final_wastage_reason',
        'completion_remarks',
        'started_at',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'required_carton_qty' => 'float',
        'final_finished_qty' => 'float',
        'rejected_cartons_qty' => 'float',
        'issued_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stageEntries()
    {
        return $this->hasMany(JobIssueStageEntry::class)->latest('start_at');
    }

    public function breakdowns()
    {
        return $this->hasMany(JobIssueBreakdown::class);
    }

    public function wastages()
    {
        return $this->hasMany(JobIssueWastage::class);
    }

    public function reelConsumptions()
    {
        return $this->hasMany(JobIssueReelConsumption::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
