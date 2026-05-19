<?php

namespace App\Domains\Production\Actions;

use App\Models\JobCard;
use App\Models\JobCardItem;
use App\Models\JobCardStep;
use App\Models\JobCardPiece;
use App\Models\JobCardLayer;
use App\Domains\Production\DTOs\JobCardDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreateJobCardAction
{
    public function execute(JobCardDTO $dto): JobCard
    {
        return DB::transaction(function () use ($dto) {
            // 1. Create Job Card
            $jobCard = JobCard::create([
                'job_card_no' => $this->generateJobCardNo($dto->job_card_no),
                'customer_id' => $dto->customer_id,
                'fg_product_id' => $dto->fg_product_id,
                'planned_qty' => $dto->planned_qty,
                'planned_date' => $dto->planned_date,
                'delivery_date' => $dto->delivery_date,
                'specifications' => $dto->specifications,
                'notes' => $dto->notes,
                'created_by' => Auth::id() ?? 1,
                
                // Packaging specific
                'length_mm' => $dto->length_mm,
                'width_mm' => $dto->width_mm,
                'height_mm' => $dto->height_mm,
                'uom' => $dto->uom,
                'deckle_size' => $dto->deckle_size,
                'sheet_length' => $dto->sheet_length,
                'ups' => $dto->ups,
                'carton_type' => $dto->carton_type,
                'machine_name' => $dto->machine_name,
                'target_speed' => $dto->target_speed,
                'printing_process' => $dto->printing_process,
                'pasting_closure' => $dto->pasting_closure,
                'printing_colors_count' => $dto->printing_colors_count,
                'pantone_colors' => $dto->pantone_colors,
                'special_details' => $dto->special_details,
                'pieces_count' => $dto->pieces_count,
                'est_unit_weight' => $dto->est_unit_weight,
            ]);

            // 2. Add RM Requirements
            foreach ($dto->items as $item) {
                JobCardItem::create([
                    'job_card_id' => $jobCard->id,
                    'rm_item_id' => $item['rm_item_id'],
                    'required_qty' => $item['required_qty'],
                    'unit' => $item['unit'] ?? 'KG',
                ]);
            }

            // 3. Add Layers (for pieces_count == 1)
            if ($dto->pieces_count == 1 && !empty($dto->layers)) {
                foreach ($dto->layers as $index => $layer) {
                    JobCardLayer::create([
                        'job_card_id' => $jobCard->id,
                        'job_card_piece_id' => null,
                        'layer_type' => $layer['layer_type'],
                        'paper_name' => $layer['paper_name'],
                        'gsm' => (int)($layer['gsm'] ?? 0),
                        'flute_profile' => $layer['flute_profile'] ?? 'Flat',
                        'sequence' => $index + 1,
                    ]);
                }
            }

            // 4. Add Pieces & Pieces' Layers (for pieces_count > 1)
            if ($dto->pieces_count > 1 && !empty($dto->pieces)) {
                foreach ($dto->pieces as $pieceData) {
                    $piece = JobCardPiece::create([
                        'job_card_id' => $jobCard->id,
                        'piece_name' => $pieceData['piece_name'],
                        'length_mm' => $pieceData['length_mm'] ?? null,
                        'width_mm' => $pieceData['width_mm'] ?? null,
                        'height_mm' => $pieceData['height_mm'] ?? null,
                        'deckle_size' => $pieceData['deckle_size'] ?? null,
                        'sheet_length' => $pieceData['sheet_length'] ?? null,
                        'ups' => $pieceData['ups'] ?? 1,
                        'machine_name' => $pieceData['machine_name'] ?? null,
                        'target_speed' => $pieceData['target_speed'] ?? 0,
                        'est_unit_weight' => $pieceData['est_unit_weight'] ?? 0,
                        'instructions' => $pieceData['instructions'] ?? null,
                    ]);

                    if (!empty($pieceData['layers'])) {
                        foreach ($pieceData['layers'] as $index => $layer) {
                            JobCardLayer::create([
                                'job_card_id' => null,
                                'job_card_piece_id' => $piece->id,
                                'layer_type' => $layer['layer_type'],
                                'paper_name' => $layer['paper_name'],
                                'gsm' => (int)($layer['gsm'] ?? 0),
                                'flute_profile' => $layer['flute_profile'] ?? 'Flat',
                                'sequence' => $index + 1,
                            ]);
                        }
                    }
                }
            }

            // 5. Add Process Steps
            $defaultSteps = [
                'Corrugation', 'Pasting', 'Printing', 
                'Die-cutting', 'Stitching/Gluing', 'Packing'
            ];

            $steps = !empty($dto->steps) ? $dto->steps : array_map(fn($name, $index) => [
                'step_name' => $name,
                'sequence' => $index + 1
            ], $defaultSteps, array_keys($defaultSteps));

            foreach ($steps as $step) {
                JobCardStep::create([
                    'job_card_id' => $jobCard->id,
                    'step_name' => $step['step_name'],
                    'sequence' => $step['sequence'],
                    'status' => 'Pending',
                ]);
            }

            return $jobCard;
        });
    }

    private function generateJobCardNo($requestedNo): string
    {
        if ($requestedNo && !JobCard::where('job_card_no', $requestedNo)->exists()) {
            return $requestedNo;
        }

        $prefix = 'JC-' . date('Y');
        $last = JobCard::where('job_card_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$last) {
            return $prefix . '-0001';
        }

        $number = (int) substr($last->job_card_no, -4);
        return $prefix . '-' . str_pad($number + 1, 4, '0', STR_PAD_LEFT);
    }
}
