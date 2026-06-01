<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMSubcategory extends Model
{
    use HasFactory;

    protected $table = 'rm_subcategories';

    protected $fillable = [
        'rm_category_id',
        'name',
        'slug',
        'is_system',
        'status',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(RMCategory::class, 'rm_category_id');
    }

    public function items()
    {
        return $this->hasMany(RMItem::class, 'rm_subcategory_id');
    }
}
