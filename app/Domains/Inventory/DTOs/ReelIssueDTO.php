<?php

namespace App\Domains\Inventory\DTOs;

use App\Support\Contracts\DataTransferObject;
use App\Http\Requests\Inventory\StoreReelIssueRequest;

class ReelIssueDTO implements DataTransferObject
{
    public function __construct(
        public readonly string $reelNo,
        public readonly string $issueDate,
        public readonly float $quantityIssued,
        public readonly float $returnToStockWeight,
        public readonly ?string $returnLocation,
        public readonly string $issuedTo,
        public readonly ?string $remarks,
    ) {}

    public static function fromRequest(StoreReelIssueRequest $request): self
    {
        return new self(
            $request->validated('reel_no'),
            $request->validated('issue_date'),
            (float) $request->validated('quantity_issued'),
            (float) ($request->validated('return_to_stock_weight') ?? 0),
            $request->validated('return_location'),
            $request->validated('issued_to'),
            $request->validated('remarks'),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
