<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCardItem extends Model
{
    protected $fillable = ['job_card_id', 'rm_item_id', 'required_qty', 'consumed_qty', 'unit'];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function item()
    {
        return $this->belongsTo(RMItem::class, 'rm_item_id');
    }
}
