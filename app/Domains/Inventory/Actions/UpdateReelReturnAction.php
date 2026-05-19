<?php

namespace App\Domains\Inventory\Actions;

use App\Events\InventoryUpdated;
use App\Models\Reel;
use App\Models\ReelReturn;
use Illuminate\Support\Facades\DB;
use Exception;

class UpdateReelReturnAction
{
    public function execute(ReelReturn $return, array $data): ReelReturn
    {
        $updatedReturn = DB::transaction(function () use ($return, $data) {
            if ($return->returned_to !== 'supplier') {
                throw new Exception('Only supplier returns can be edited.', 400);
            }

            $reel = $return->reel;
            if (!$reel) {
                throw new Exception('Associated reel not found.', 404);
            }

            $oldWeight = (float) $return->remaining_weight;
            $restoredBalance = min($reel->balance_weight + $oldWeight, $reel->original_weight);
            $newWeight = (float) $data['remaining_weight'];

            if ($newWeight > $restoredBalance + 0.01) {
                throw new Exception('Returned weight cannot exceed current balance weight.', 400);
            }

            $return->return_date = $data['return_date'];
            $return->remaining_weight = $newWeight;
            $return->return_location = $data['return_location'] ?? null;
            $return->condition = $data['condition'];
            $return->vehicle_number = $data['vehicle_number'] ?? null;
            $return->return_to_supplier_id = $data['return_to_supplier_id'] ?? null;
            $return->remarks = $data['remarks'] ?? null;
            $return->save();

            $reel->balance_weight = max($restoredBalance - $newWeight, 0);
            $this->updateReelStatus($reel);
            
            $this->validateAndSyncBalance($reel);
            $reel->save();

            return $return->load('reel.paperQuality', 'reel.supplier');
        });

        InventoryUpdated::dispatch('return_updated', ['id' => $updatedReturn->id]);

        return $updatedReturn;
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
