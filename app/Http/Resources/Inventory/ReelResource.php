<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class ReelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'reel_no'          => $this->reel_no,
            'paper_quality_id' => $this->paper_quality_id,
            'supplier_id'      => $this->supplier_id,
            'reel_size'        => $this->reel_size,
            'original_weight'  => $this->original_weight,
            'balance_weight'   => $this->balance_weight,
            'status'           => $this->status,
            'paper_quality'    => $this->whenLoaded('paperQuality'),
            'supplier'         => $this->whenLoaded('supplier'),
        ];
    }
}
