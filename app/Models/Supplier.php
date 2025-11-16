<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

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
