<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class ReelReceiptResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'lot_number'        => $this->lot_number,
            'po_number'         => $this->po_number,
            'grn_number'        => $this->grn_number,
            'receiving_date'    => $this->receiving_date,
            'received_by'       => $this->received_by,
            'gsm'               => $this->gsm,
            'bursting_strength' => $this->bursting_strength,
            'rate_per_kg'       => $this->rate_per_kg,
            'qc_status'         => $this->qc_status,
            'remarks'           => $this->remarks,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
            'reel'              => new ReelResource($this->whenLoaded('reel')),
        ];
    }
}
