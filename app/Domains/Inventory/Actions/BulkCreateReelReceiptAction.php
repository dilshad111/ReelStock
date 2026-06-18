<?php

namespace App\Domains\Inventory\Actions;

use App\Events\InventoryUpdated;
use App\Models\Reel;
use App\Models\ReelReceipt;
use App\Services\ReelNumberService;
use App\Services\LotNumberService;
use Illuminate\Support\Facades\DB;

class BulkCreateReelReceiptAction
{
    public function execute(array $commonData, array $reelsData): array
    {
        $createdReceipts = DB::transaction(function () use ($commonData, $reelsData) {
            $reelNumbers = ReelNumberService::generateNextNumbers(count($reelsData));
            $lotNumber = LotNumberService::generateNextNumber();
            
            $createdReceipts = [];

            foreach ($reelsData as $index => $reelData) {
                $reel = Reel::create([
                    'reel_no'          => $reelNumbers[$index],
                    'paper_quality_id' => $commonData['paper_quality_id'],
                    'supplier_id'      => $commonData['supplier_id'],
                    'reel_size'        => $reelData['reel_size'],
                    'original_weight'  => $reelData['reel_weight'],
                    'balance_weight'   => $reelData['reel_weight'],
                    'status'           => 'in_stock',
                    'current_location' => 'Warehouse',
                ]);

                $createdReceipts[] = ReelReceipt::create([
                    'reel_id'           => $reel->id,
                    'lot_number'        => $lotNumber,
                    'po_number'         => $commonData['po_number'] ?? null,
                    'grn_number'        => $commonData['grn_number'] ?? null,
                    'receiving_date'    => $commonData['receiving_date'],
                    'received_by'       => $commonData['received_by'],
                    'gsm'               => $commonData['gsm'],
                    'bursting_strength' => $commonData['bursting_strength'],
                    'rate_per_kg'       => $commonData['rate_per_kg'],
                    'qc_status'         => $commonData['qc_status'],
                    'remarks'           => $commonData['remarks'],
                ]);
            }

            return $createdReceipts;
        });

        InventoryUpdated::dispatch('receipt_created', ['receipt_ids' => collect($createdReceipts)->pluck('id')]);

        return $createdReceipts;
    }
}
