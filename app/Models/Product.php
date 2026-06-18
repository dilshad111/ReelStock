<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Product extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    public const POLICY_SHARED = 'shared_product';
    public const POLICY_RESTRICTED = 'customer_restricted';

    protected $fillable = [
        'customer_id',
        'item_code',
        'item_name',
        'rate',
        'opening_balance',
        'dispatch_policy',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'rate' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function fgReceipts()
    {
        return $this->hasMany(FGReceipt::class);
    }

    public function fgDispatches()
    {
        return $this->hasMany(FGDispatch::class);
    }

    public function stockLedger()
    {
        return $this->hasMany(FGStockLedger::class);
    }

    public function customerLinks()
    {
        return $this->hasMany(FGProductCustomerLink::class);
    }

    /**
     * Calculate the current stock balance from the ledger.
     */
    public function getCurrentBalanceAttribute()
    {
        $lastEntry = $this->stockLedger()->latest('id')->first();
        return $lastEntry ? $lastEntry->balance_after : 0;
    }
}
