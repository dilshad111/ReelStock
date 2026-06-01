<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMCategory extends Model
{
    use HasFactory;

    protected $table = 'rm_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'status',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function subcategories()
    {
        return $this->hasMany(RMSubcategory::class, 'rm_category_id');
    }

    public function items()
    {
        return $this->hasMany(RMItem::class, 'rm_category_id');
    }
}
