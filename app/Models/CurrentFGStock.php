<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentFGStock extends Model
{
    use HasFactory;

    protected $table = 'current_fg_stock';

    public $timestamps = false; // We use last_updated_at column manually or via database timestamp

    protected $fillable = ['product_id', 'warehouse_id', 'quantity'];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
