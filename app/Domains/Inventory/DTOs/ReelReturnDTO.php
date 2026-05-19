<?php

namespace App\Domains\Inventory\DTOs;

use App\Support\Contracts\DataTransferObject;
use App\Http\Requests\Inventory\StoreReelReturnRequest;

class ReelReturnDTO implements DataTransferObject
{
    public function __construct(
        public readonly string $reelNo,
        public readonly ?string $challanNo,
        public readonly string $returnDate,
        public readonly float $remainingWeight,
        public readonly string $returnedTo,
        public readonly ?string $returnLocation,
        public readonly string $condition,
        public readonly ?string $vehicleNumber,
        public readonly ?int $returnToSupplierId,
        public readonly ?string $remarks,
    ) {}

    public static function fromRequest(StoreReelReturnRequest $request): self
    {
        return new self(
            $request->validated('reel_no'),
            $request->validated('challan_no'),
            $request->validated('return_date'),
            (float) $request->validated('remaining_weight'),
            $request->validated('returned_to'),
            $request->validated('return_location'),
            $request->validated('condition'),
            $request->validated('vehicle_number'),
            $request->validated('return_to_supplier_id'),
            $request->validated('remarks'),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
