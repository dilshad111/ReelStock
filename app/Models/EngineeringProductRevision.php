<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngineeringProductRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'engineering_product_id',
        'revision_number',
        'revision_date',
        'change_notes',
        'snapshot_data',
        'created_by',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'revision_date' => 'date',
        'snapshot_data' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(EngineeringProduct::class, 'engineering_product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
