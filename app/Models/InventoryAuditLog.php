<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAuditLog extends Model
{
    use HasFactory;

    protected $table = 'inventory_audit_log';

    public $timestamps = false; // We use action_timestamp custom field

    protected $fillable = [
        'user_id', 'transaction_type', 'document_number', 'product_id',
        'warehouse_id', 'old_quantity', 'new_quantity', 'reason_for_change', 'ip_address'
    ];

    protected $casts = [
        'old_quantity' => 'decimal:4',
        'new_quantity' => 'decimal:4',
        'action_timestamp' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
