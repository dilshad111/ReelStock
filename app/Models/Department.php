<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_name',
        'created_by',
        'updated_by',
    ];

    public function machines()
    {
        return $this->hasMany(ProductionMachine::class);
    }
}
