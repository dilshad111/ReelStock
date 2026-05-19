<?php

namespace App\Domains\Inventory\Actions;

use App\Events\InventoryUpdated;
use App\Models\ReelReceipt;
use Illuminate\Support\Facades\DB;

class UpdateReelReceiptAction
{
    public function execute(ReelReceipt $receipt, array $receiptData, array $reelData, ?float $originalWeight = null): ReelReceipt
    {
        $updatedReceipt = DB::transaction(function () use ($receipt, $receiptData, $reelData, $originalWeight) {
            $reel = $receipt->reel;

            if (!empty($receiptData)) {
                $receipt->update($receiptData);
            }

            if (!empty($reelData)) {
                $reel->update($reelData);
            }

            if ($originalWeight !== null) {
                $reel->original_weight = $originalWeight;
                $reel->save();
            }

            // Always sync balance and status after any potential change
            $reel->syncBalance();

            return $receipt->fresh('reel.paperQuality', 'reel.supplier');
        });

        InventoryUpdated::dispatch('receipt_updated', ['id' => $updatedReceipt->id]);

        return $updatedReceipt;
    }
}
