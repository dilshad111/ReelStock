<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_card_no',
        'current_version_no',
        'active_version_id',
        'customer_id',
        'fg_product_id',
        'planned_qty',
        'planned_date',
        'delivery_date',
        'status',
        'specifications',
        'notes',
        'created_by',
        'length_mm',
        'width_mm',
        'height_mm',
        'uom',
        'deckle_size',
        'sheet_length',
        'ups',
        'carton_type',
        'machine_name',
        'target_speed',
        'printing_process',
        'pasting_closure',
        'printing_colors_count',
        'pantone_colors',
        'special_details',
        'pieces_count',
        'est_unit_weight'
    ];

    protected $casts = [
        'pantone_colors' => 'array',
        'special_details' => 'array',
        'planned_qty' => 'float',
        'length_mm' => 'float',
        'width_mm' => 'float',
        'height_mm' => 'float',
        'deckle_size' => 'float',
        'sheet_length' => 'float',
        'est_unit_weight' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'fg_product_id');
    }

    public function items()
    {
        return $this->hasMany(JobCardItem::class);
    }

    public function steps()
    {
        return $this->hasMany(JobCardStep::class)->orderBy('sequence');
    }

    public function pieces()
    {
        return $this->hasMany(JobCardPiece::class);
    }

    public function layers()
    {
        return $this->hasMany(JobCardLayer::class)->orderBy('sequence');
    }

    public function productionLogs()
    {
        return $this->hasMany(ProductionLog::class);
    }

    public function jobIssues()
    {
        return $this->hasMany(JobIssue::class);
    }

    public function versions()
    {
        return $this->hasMany(JobCardVersion::class)->orderBy('version_no', 'desc');
    }

    public function activeVersion()
    {
        return $this->belongsTo(JobCardVersion::class, 'active_version_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
