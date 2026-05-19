<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Resources\Json\JsonResource;

class ReelIssueResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                     => $this->id,
            'issue_date'             => $this->issue_date,
            'quantity_issued'        => $this->quantity_issued,
            'return_to_stock_weight' => $this->return_to_stock_weight,
            'net_consumed_weight'    => $this->net_consumed_weight,
            'return_location'        => $this->return_location,
            'issued_to'              => $this->issued_to,
            'remarks'                => $this->remarks,
            'auto_return_id'         => $this->auto_return_id,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
            'reel'                   => new ReelResource($this->whenLoaded('reel')),
        ];
    }
}
