<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class FGReceipt extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'fg_receipts';

    protected $fillable = [
        'date', 'customer_id', 'product_id', 'job_number',
        'production_date', 'quantity_produced', 'wastage',
        'remarks', 'created_by'
    ];

    protected $casts = [
        'quantity_produced' => 'decimal:2',
        'wastage' => 'decimal:2',
        'date' => 'date',
        'production_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
