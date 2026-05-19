<?php

namespace App\Domains\Inventory\Actions;

use App\Models\Reel;
use App\Models\ReelReceipt;
use App\Services\ReelNumberService;
use App\Services\LotNumberService;
use App\Domains\Inventory\DTOs\ReelReceiptDTO;
use Illuminate\Support\Facades\DB;

class CreateReelReceiptAction
{
    public function execute(ReelReceiptDTO $dto): ReelReceipt
    {
        return DB::transaction(function () use ($dto) {
            $reelNo = ReelNumberService::generateNextNumber();
            $lotNumber = LotNumberService::generateNextNumber();

            $reel = Reel::create([
                'reel_no'          => $reelNo,
                'paper_quality_id' => $dto->paperQualityId,
                'supplier_id'      => $dto->supplierId,
                'reel_size'        => $dto->reelSize,
                'original_weight'  => $dto->reelWeight,
                'balance_weight'   => $dto->reelWeight,
                'status'           => 'in_stock',
            ]);

            $receipt = ReelReceipt::create([
                'reel_id'           => $reel->id,
                'lot_number'        => $lotNumber,
                'po_number'         => $dto->poNumber,
                'grn_number'        => $dto->grnNumber,
                'receiving_date'    => $dto->receivingDate,
                'received_by'       => $dto->receivedBy,
                'gsm'               => $dto->gsm,
                'bursting_strength' => $dto->burstingStrength,
                'rate_per_kg'       => $dto->ratePerKg,
                'qc_status'         => $dto->qcStatus,
                'remarks'           => $dto->remarks,
            ]);

            event(new \App\Events\InventoryUpdated('receipt_created', $receipt->load('reel')));

            return $receipt;
        });
    }
}
