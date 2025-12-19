<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Supplier extends Model implements Auditable
{
    use HasFactory, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'supplier_id',
        'name',
        'contact_person',
        'address',
        'phone',
        'email',
        'notes',
    ];

    public function reels()
    {
        return $this->hasMany(Reel::class);
    }
}
