<?php

namespace App\Domains\Inventory\Actions;

use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReturn;
use App\Domains\Inventory\DTOs\ReelIssueDTO;
use Illuminate\Support\Facades\DB;
use Exception;

class CreateReelIssueAction
{
    public function execute(ReelIssueDTO $dto): ReelIssue
    {
        return DB::transaction(function () use ($dto) {
            $reel = Reel::where('reel_no', $dto->reelNo)->lockForUpdate()->first();

            if (!$reel) {
                throw new Exception('Reel not found', 404);
            }

            if ($dto->returnToStockWeight > $dto->quantityIssued) {
                throw new Exception('Return to stock weight cannot exceed quantity issued', 422);
            }

            if ($reel->balance_weight < $dto->quantityIssued) {
                throw new Exception('Insufficient balance weight', 400);
            }

            $netConsumed = max($dto->quantityIssued - $dto->returnToStockWeight, 0);

            $issue = ReelIssue::create([
                'reel_id'                => $reel->id,
                'issue_date'             => $dto->issueDate,
                'quantity_issued'        => $dto->quantityIssued,
                'return_to_stock_weight' => $dto->returnToStockWeight,
                'net_consumed_weight'    => $netConsumed,
                'return_location'        => $dto->returnLocation,
                'issued_to'              => $dto->issuedTo,
                'remarks'                => $dto->remarks,
            ]);

            $newBalance = max($reel->balance_weight - $netConsumed, 0);
            $reel->balance_weight = $newBalance;
            $this->refreshReelStatus($reel);
            
            // Validate and sync balance with transaction history
            $this->validateAndSyncBalance($reel);
            $reel->save();

            $autoReturnId = null;
            if ($dto->returnToStockWeight > 0) {
                $autoReturn = ReelReturn::create([
                    'reel_id'          => $reel->id,
                    'return_date'      => $dto->issueDate,
                    'remaining_weight' => $dto->returnToStockWeight,
                    'returned_to'      => 'stock',
                    'return_location'  => $dto->returnLocation,
                    'condition'        => 'good',
                    'remarks'          => $dto->remarks ? ($dto->remarks . ' (auto return)') : 'Auto return from issue form',
                ]);
                $autoReturnId = $autoReturn->id;
            }

            if ($autoReturnId) {
                $issue->auto_return_id = $autoReturnId;
                $issue->save();
            }

            event(new \App\Events\InventoryUpdated('issue_created', $issue->load('reel')));

            return $issue;
        });
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
