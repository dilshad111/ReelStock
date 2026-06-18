<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FGProductCustomerLink extends Model
{
    use HasFactory;

    protected $table = 'fg_product_customer_links';

    protected $fillable = [
        'product_id',
        'customer_id',
        'customer_item_code',
        'customer_item_name',
        'customer_rate',
        'is_default',
        'status',
    ];

    protected $casts = [
        'customer_rate' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
