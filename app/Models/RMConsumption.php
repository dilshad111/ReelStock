<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class RMConsumption extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'rm_consumptions';

    protected $fillable = [
        'voucher_no', 'job_card_id', 'date', 'department', 'issued_to', 'notes', 'created_by'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(RMConsumptionItem::class, 'rm_consumption_id');
    }
}
