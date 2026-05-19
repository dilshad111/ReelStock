<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCardPiece extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_card_id',
        'piece_name',
        'length_mm',
        'width_mm',
        'height_mm',
        'deckle_size',
        'sheet_length',
        'ups',
        'machine_name',
        'target_speed',
        'est_unit_weight',
        'instructions',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function layers()
    {
        return $this->hasMany(JobCardLayer::class, 'job_card_piece_id')->orderBy('sequence');
    }
}
