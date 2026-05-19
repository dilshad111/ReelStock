<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCardLayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_card_id',
        'job_card_piece_id',
        'layer_type',
        'paper_name',
        'gsm',
        'flute_profile',
        'sequence',
    ];

    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }

    public function piece()
    {
        return $this->belongsTo(JobCardPiece::class, 'job_card_piece_id');
    }
}
