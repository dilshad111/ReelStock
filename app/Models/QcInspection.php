<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class QcInspection extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'lot_number',
        'paper_quality_id',
        'supplier_id',
        'po_number',
        'grn_number',
        'received_date',
        'inspection_date',
        'inspector_name',
        'qc_status',
        'decision_type',
        'remarks',
        'inspected_by',
        'avg_gsm',
        'avg_bursting',
        'avg_moisture',
        'avg_cobb',
    ];

    protected $casts = [
        'received_date' => 'date',
        'inspection_date' => 'date',
        'avg_gsm' => 'decimal:2',
        'avg_bursting' => 'decimal:2',
        'avg_moisture' => 'decimal:2',
        'avg_cobb' => 'decimal:2',
    ];

    public function paperQuality()
    {
        return $this->belongsTo(PaperQuality::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function details()
    {
        return $this->hasMany(QcInspectionDetail::class);
    }

    /**
     * Recalculate average values from detail records
     */
    public function recalculateAverages()
    {
        $details = $this->details()->whereNotNull('gsm')->get();

        if ($details->count() > 0) {
            $this->avg_gsm = $details->avg('gsm');
            $this->avg_bursting = $details->whereNotNull('bursting')->avg('bursting');
            $this->avg_moisture = $details->whereNotNull('moisture')->avg('moisture');
            $this->avg_cobb = $details->whereNotNull('cobb')->avg('cobb');
        }

        $this->save();
    }

    /**
     * Determine QC status based on detail results
     */
    public function determineStatus()
    {
        $details = $this->details;

        if ($details->count() === 0) {
            $this->qc_status = 'pending';
        } elseif ($details->where('is_passed', false)->count() > 0) {
            $this->qc_status = 'rejected';
        } else {
            // Check if any detail has test results entered
            $hasResults = $details->filter(function ($d) {
                return $d->gsm !== null || $d->bursting !== null || $d->moisture !== null || $d->cobb !== null;
            })->count() > 0;

            $this->qc_status = $hasResults ? 'approved' : 'pending';
        }

        $this->save();
    }
}
