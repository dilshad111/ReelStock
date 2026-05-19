<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class QcInspectionDetail extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'qc_inspection_id',
        'reel_id',
        'reel_no',
        'reel_size',
        'reel_weight',
        'gsm',
        'bursting',
        'moisture',
        'ash',
        'cobb',
        'is_passed',
        'failed_params',
    ];

    protected $casts = [
        'reel_size' => 'decimal:2',
        'reel_weight' => 'decimal:2',
        'gsm' => 'decimal:2',
        'bursting' => 'decimal:2',
        'moisture' => 'decimal:2',
        'ash' => 'decimal:2',
        'cobb' => 'decimal:2',
        'is_passed' => 'boolean',
        'failed_params' => 'array',
    ];

    public function inspection()
    {
        return $this->belongsTo(QcInspection::class, 'qc_inspection_id');
    }

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }

    /**
     * Validate this detail against quality criteria
     */
    public function validateAgainstCriteria($criteria)
    {
        $failedParams = [];

        if ($criteria->min_gsm !== null && $this->gsm !== null && $this->gsm < $criteria->min_gsm) {
            $failedParams[] = 'gsm_min';
        }
        if ($criteria->max_gsm !== null && $this->gsm !== null && $this->gsm > $criteria->max_gsm) {
            $failedParams[] = 'gsm_max';
        }

        if ($criteria->min_bursting !== null && $this->bursting !== null && $this->bursting < $criteria->min_bursting) {
            $failedParams[] = 'bursting_min';
        }
        if ($criteria->max_bursting !== null && $this->bursting !== null && $this->bursting > $criteria->max_bursting) {
            $failedParams[] = 'bursting_max';
        }

        if ($criteria->max_moisture !== null && $this->moisture !== null && $this->moisture > $criteria->max_moisture) {
            $failedParams[] = 'moisture_max';
        }
        if ($criteria->min_moisture !== null && $this->moisture !== null && $this->moisture < $criteria->min_moisture) {
            $failedParams[] = 'moisture_min';
        }

        if ($criteria->max_cobb !== null && $this->cobb !== null && $this->cobb > $criteria->max_cobb) {
            $failedParams[] = 'cobb_max';
        }
        if ($criteria->min_cobb !== null && $this->cobb !== null && $this->cobb < $criteria->min_cobb) {
            $failedParams[] = 'cobb_min';
        }

        $this->failed_params = count($failedParams) > 0 ? $failedParams : null;
        $this->is_passed = count($failedParams) === 0;

        return $this->is_passed;
    }
}
