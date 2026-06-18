<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EngineeringProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_number',
        'product_created_date',
        'product_code',
        'product_name',
        'customer_id',
        'product_category',
        'fefco_code',
        'revision_number',
        'revision_date',
        'status',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'product_created_date' => 'date',
        'revision_number' => 'integer',
        'revision_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function components()
    {
        return $this->hasMany(EngineeringProductComponent::class)->orderBy('sort_order');
    }

    public function revisions()
    {
        return $this->hasMany(EngineeringProductRevision::class)->orderByDesc('revision_number');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
