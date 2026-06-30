<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FGDamage extends Model
{
    use HasFactory;

    protected $table = 'fg_damages';

    protected $fillable = [
        'damage_number',
        'date',
        'customer_id',
        'product_id',
        'warehouse_id',
        'job_card_id',
        'job_number',
        'quantity',
        'reason',
        'remarks',
        'approved_by',
        'created_by',
        'status',
        'reversed_at',
        'reversed_by',
        'reversal_reason'
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
        'reversed_at' => 'datetime'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reverser()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
