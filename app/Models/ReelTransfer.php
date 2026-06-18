<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ReelTransfer extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    public const LOCATION_FACTORY = 'Factory';
    public const LOCATION_WAREHOUSE = 'Warehouse';

    protected $fillable = [
        'reel_id',
        'transfer_date',
        'from_location',
        'to_location',
        'handled_by',
        'remarks',
        'created_by',
    ];

    public function reel()
    {
        return $this->belongsTo(Reel::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
