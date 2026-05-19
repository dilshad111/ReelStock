<?php

namespace App\Domains\Inventory\Actions;

use App\Events\InventoryUpdated;
use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReturn;
use Illuminate\Support\Facades\DB;
use Exception;

class DeleteReelIssueAction
{
    public function execute(int $id): void
    {
        DB::transaction(function () use ($id) {
            $issue = ReelIssue::lockForUpdate()->findOrFail($id);
            $reel = Reel::lockForUpdate()->findOrFail($issue->reel_id);

            // Delete associated auto-return if it exists
            if ($issue->auto_return_id) {
                ReelReturn::where('id', $issue->auto_return_id)->delete();
            }

            // Delete the issue itself before synchronizing balance
            // This ensures logic in validateAndSyncBalance considers the issue gone
            $issue->delete();

            // Refresh reel balance from the transaction history
            $this->validateAndSyncBalance($reel);
            $this->refreshReelStatus($reel);
            $reel->save();
        });

        InventoryUpdated::dispatch('issue_deleted', ['id' => $id]);
    }

    protected function refreshReelStatus(Reel $reel): void
    {
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
