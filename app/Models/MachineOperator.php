<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineOperator extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_name',
        'machine_id',
        'created_by',
        'updated_by',
    ];

    public function machine()
    {
        return $this->belongsTo(ProductionMachine::class, 'machine_id');
    }
}
