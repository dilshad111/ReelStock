<?php

namespace App\Domains\Inventory\Actions;

use App\Events\InventoryUpdated;
use App\Models\ReelReceipt;
use Illuminate\Support\Facades\DB;
use Exception;

class DeleteReelReceiptAction
{
    public function execute(ReelReceipt $receipt): void
    {
        $id = $receipt->id;
        DB::transaction(function () use ($receipt) {
            $reel = $receipt->reel;

            if ($reel && ($reel->issues()->exists() || $reel->returns()->exists())) {
                throw new Exception('Cannot delete this receipt because the associated reel has issue or return records.');
            }

            $receipt->delete();

            if ($reel && !$reel->receipts()->exists()) {
                $reel->delete();
            }
        });

        InventoryUpdated::dispatch('receipt_deleted', ['id' => $id]);
    }
}
