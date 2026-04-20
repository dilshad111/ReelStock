<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartageBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'transporter_id', 'bill_to', 'bill_date', 'total_amount', 
        'status', 'tax_type', 'tax_percentage', 'tax_amount', 'net_amount', 
        'approved_by', 'approved_at'
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function transporter()
    {
        return $this->belongsTo(Transporter::class);
    }

    public function entries()
    {
        return $this->hasMany(CartageEntry::class);
    }
}
