<?php

namespace App\Domains\RawMaterial\DTOs;

class RMReceiptDTO
{
    public function __construct(
        public readonly string $grn_no,
        public readonly int $supplier_id,
        public readonly string $date,
        public readonly ?string $remarks,
        public readonly ?string $attachment_path,
        public readonly int $created_by,
        public readonly array $items // Array of items with rm_item_id, quantity, unit, rate, total_amount
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            grn_no: $request->grn_no,
            supplier_id: $request->supplier_id,
            date: $request->date,
            remarks: $request->remarks,
            attachment_path: $request->attachment_path,
            created_by: $request->user()->id,
            items: $request->items
        );
    }
}
