<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class ReelReturnResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->id,
            'challan_no'            => $this->challan_no,
            'return_date'           => $this->return_date,
            'remaining_weight'      => $this->remaining_weight,
            'returned_to'           => $this->returned_to,
            'return_location'       => $this->return_location,
            'condition'             => $this->condition,
            'vehicle_number'        => $this->vehicle_number,
            'return_to_supplier_id' => $this->return_to_supplier_id,
            'remarks'               => $this->remarks,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
            'reel'                  => new ReelResource($this->whenLoaded('reel')),
            'return_to_supplier'    => $this->whenLoaded('returnToSupplier'),
        ];
    }
}
