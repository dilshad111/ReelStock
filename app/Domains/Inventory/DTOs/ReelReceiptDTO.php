<?php

namespace App\Domains\Inventory\DTOs;

use App\Support\Contracts\DataTransferObject;
use App\Http\Requests\Inventory\StoreReelReceiptRequest;

class ReelReceiptDTO implements DataTransferObject
{
    public function __construct(
        public readonly int $paperQualityId,
        public readonly int $supplierId,
        public readonly float $reelSize,
        public readonly float $reelWeight,
        public readonly string $receivingDate,
        public readonly ?string $receivedBy,
        public readonly ?float $gsm,
        public readonly ?float $burstingStrength,
        public readonly ?float $ratePerKg,
        public readonly string $qcStatus,
        public readonly ?string $remarks,
        public readonly ?string $poNumber,
        public readonly ?string $grnNumber,
    ) {}

    public static function fromRequest(StoreReelReceiptRequest $request): self
    {
        return new self(
            $request->validated('paper_quality_id'),
            $request->validated('supplier_id'),
            (float) $request->validated('reel_size'),
            (float) $request->validated('reel_weight'),
            $request->validated('receiving_date'),
            $request->validated('received_by'),
            $request->filled('gsm') ? (float) $request->validated('gsm') : null,
            $request->filled('bursting_strength') ? (float) $request->validated('bursting_strength') : null,
            $request->filled('rate_per_kg') ? (float) $request->validated('rate_per_kg') : null,
            $request->validated('qc_status'),
            $request->validated('remarks'),
            $request->validated('po_number'),
            $request->validated('grn_number'),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
