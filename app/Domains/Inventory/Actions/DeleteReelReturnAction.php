<?php

namespace App\Domains\Inventory\Actions;

use App\Events\InventoryUpdated;
use App\Models\Reel;
use App\Models\ReelReturn;
use Illuminate\Support\Facades\DB;
use Exception;

class DeleteReelReturnAction
{
    public function execute(int $id): void
    {
        DB::transaction(function () use ($id) {
            $return = ReelReturn::lockForUpdate()->findOrFail($id);
            $reel = Reel::lockForUpdate()->findOrFail($return->reel_id);

            if ($return->returned_to !== 'supplier') {
                throw new Exception('Only supplier returns can be deleted.', 400);
            }

            $return->delete();

            $this->validateAndSyncBalance($reel);
            $this->updateReelStatus($reel);
            $reel->save();
        });

        InventoryUpdated::dispatch('return_deleted', ['id' => $id]);
    }

    protected function updateReelStatus(Reel $reel): void
    {
        $supplierReturn = $reel->returns()
            ->where('returned_to', 'supplier')
            ->orderBy('return_date', 'desc')
            ->first();
        
        if ($supplierReturn) {
            $reel->status = 'returned_to_supplier';
            return;
        }
        
        if ($reel->balance_weight <= 0) {
            $reel->status = 'fully_used';
        } elseif ($reel->balance_weight < $reel->original_weight) {
            $reel->status = 'partially_used';
        } else {
            $reel->status = 'in_stock';
        }
    }

    protected function validateAndSyncBalance(Reel $reel): bool
    {
        $totalConsumed = $reel->issues()->sum('net_consumed_weight');
        $totalReturnedToSupplier = $reel->returns()->where('returned_to', 'supplier')->sum('remaining_weight');
        $calculatedBalance = max($reel->original_weight - $totalConsumed - $totalReturnedToSupplier, 0);
        
        $difference = abs($calculatedBalance - $reel->balance_weight);
        
        if ($difference > 0.01) {
            $reel->balance_weight = max($calculatedBalance, 0);
            return false;
        }
        
        return true;
    }
}
