<?php

namespace App\Domains\RawMaterial\DTOs;

class RMConsumptionDTO
{
    public function __construct(
        public readonly string $voucher_no,
        public readonly ?int $job_card_id,
        public readonly string $date,
        public readonly ?string $department,
        public readonly ?string $issued_to,
        public readonly ?string $notes,
        public readonly int $created_by,
        public readonly array $items // Array of items with rm_item_id, quantity
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            voucher_no: $request->voucher_no,
            job_card_id: $request->job_card_id,
            date: $request->date,
            department: $request->department,
            issued_to: $request->issued_to,
            notes: $request->notes,
            created_by: $request->user()->id,
            items: $request->items
        );
    }
}
