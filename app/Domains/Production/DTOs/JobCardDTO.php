<?php

namespace App\Domains\Production\DTOs;

class JobCardDTO
{
    public function __construct(
        public readonly string $job_card_no,
        public readonly int $customer_id,
        public readonly ?int $fg_product_id,
        public readonly ?string $item_code,
        public readonly ?string $item_name,
        public readonly float $planned_qty,
        public readonly string $planned_date,
        public readonly ?string $delivery_date = null,
        public readonly ?string $specifications = null,
        public readonly ?string $notes = null,
        public readonly array $items = [], // Array of ['rm_item_id' => 1, 'required_qty' => 100]
        public readonly array $steps = [],  // Array of ['step_name' => 'Corrugation', 'sequence' => 1]
        
        // Packaging Specifications
        public readonly ?float $length_mm = null,
        public readonly ?float $width_mm = null,
        public readonly ?float $height_mm = null,
        public readonly string $uom = 'mm',
        public readonly ?float $deckle_size = null,
        public readonly ?float $sheet_length = null,
        public readonly int $ups = 1,
        public readonly string $carton_type = 'FEFCO 0201',
        public readonly ?string $machine_name = null,
        public readonly int $target_speed = 0,
        public readonly ?string $printing_process = null,
        public readonly ?string $pasting_closure = null,
        public readonly int $printing_colors_count = 0,
        public readonly ?array $pantone_colors = [],
        public readonly ?array $special_details = [],
        public readonly int $pieces_count = 1,
        public readonly float $est_unit_weight = 0,
        
        // Dynamic Ply Layers (used when pieces_count == 1)
        public readonly array $layers = [], // Array of ['layer_type' => 'Liner 1', 'paper_name' => 'Craft', 'gsm' => 125, 'flute_profile' => 'Flat']
        
        // Multi-Piece cardboard configuration (used when pieces_count > 1)
        public readonly array $pieces = [] // Array of piece details including their own layers
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            job_card_no: $request->job_card_no ?? '',
            customer_id: (int)$request->customer_id,
            fg_product_id: $request->fg_product_id ? (int)$request->fg_product_id : null,
            item_code: $request->item_code,
            item_name: $request->item_name,
            planned_qty: (float)$request->planned_qty,
            planned_date: $request->planned_date,
            delivery_date: $request->delivery_date,
            specifications: $request->specifications,
            notes: $request->notes,
            items: $request->items ?? [],
            steps: $request->steps ?? [],
            
            // Packaging
            length_mm: $request->length_mm !== null ? (float)$request->length_mm : null,
            width_mm: $request->width_mm !== null ? (float)$request->width_mm : null,
            height_mm: $request->height_mm !== null ? (float)$request->height_mm : null,
            uom: $request->uom ?? 'mm',
            deckle_size: $request->deckle_size !== null ? (float)$request->deckle_size : null,
            sheet_length: $request->sheet_length !== null ? (float)$request->sheet_length : null,
            ups: (int)($request->ups ?? 1),
            carton_type: $request->carton_type ?? 'FEFCO 0201',
            machine_name: $request->machine_name,
            target_speed: (int)($request->target_speed ?? 0),
            printing_process: $request->printing_process,
            pasting_closure: $request->pasting_closure,
            printing_colors_count: (int)($request->printing_colors_count ?? 0),
            pantone_colors: $request->pantone_colors ?? [],
            special_details: $request->special_details ?? [],
            pieces_count: (int)($request->pieces_count ?? 1),
            est_unit_weight: (float)($request->est_unit_weight ?? 0),
            
            // Layers
            layers: $request->layers ?? [],
            pieces: $request->pieces ?? []
        );
    }
}
