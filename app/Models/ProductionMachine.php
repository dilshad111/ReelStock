<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionMachine extends Model
{
    use HasFactory;

    protected $fillable = [
        'machine_name',
        'department_id',
        'base_speed',
        'minimum_speed',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'base_speed' => 'float',
        'minimum_speed' => 'float',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function operators()
    {
        return $this->hasMany(MachineOperator::class, 'machine_id');
    }
}
