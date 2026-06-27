<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class FGDispatch extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'fg_dispatches';

    protected $fillable = [
        'date', 'customer_id', 'product_id', 'warehouse_id', 'fg_product_customer_link_id',
        'dispatch_item_code', 'dispatch_item_name', 'job_number',
        'dc_number', 'quantity_dispatched', 'dispatch_rate', 'dispatch_amount',
        'remarks', 'created_by'
    ];

    protected $casts = [
        'quantity_dispatched' => 'decimal:2',
        'dispatch_rate' => 'decimal:2',
        'dispatch_amount' => 'decimal:2',
        'date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customerLink()
    {
        return $this->belongsTo(FGProductCustomerLink::class, 'fg_product_customer_link_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
