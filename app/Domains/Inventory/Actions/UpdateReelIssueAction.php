<?php

namespace App\Domains\Inventory\Actions;

use App\Models\ReelIssue;
use App\Models\ReelReturn;
use Illuminate\Support\Facades\DB;
use Exception;

class UpdateReelIssueAction
{
    public function execute(ReelIssue $issue, array $data): ReelIssue
    {
        return DB::transaction(function () use ($issue, $data) {
            $reel = $issue->reel;

            if (!$reel) {
                throw new Exception('Associated reel not found', 404);
            }

            if (isset($data['reel_no']) && strtoupper(trim($data['reel_no'])) !== strtoupper($reel->reel_no)) {
                throw new Exception('Changing the reel for an existing issue is not supported', 422);
            }

            $returnWeight = (float) ($data['return_to_stock_weight'] ?? 0);
            $quantityIssued = (float) $data['quantity_issued'];

            if ($returnWeight > $quantityIssued) {
                throw new Exception('Return to stock weight cannot exceed quantity issued', 422);
            }

            $oldNet = (float) $issue->net_consumed_weight;
            $newNet = max($quantityIssued - $returnWeight, 0);

            $balanceBeforeIssue = $reel->balance_weight + $oldNet;
            if ($newNet > $balanceBeforeIssue + 1e-6) {
                throw new Exception('Insufficient balance weight', 400);
            }

            $newBalance = max($balanceBeforeIssue - $newNet, 0);

            $issue->issue_date             = $data['issue_date'];
            $issue->quantity_issued        = $quantityIssued;
            $issue->return_to_stock_weight = $returnWeight;
            $issue->net_consumed_weight    = $newNet;
            $issue->return_location        = $data['return_location'] ?? null;
            $issue->issued_to              = $data['issued_to'];
            $issue->remarks                = $data['remarks'] ?? null;
            $issue->save();

            $reel->balance_weight = $newBalance;
            $this->refreshReelStatus($reel);
            
            $this->validateAndSyncBalance($reel);
            $reel->save();

            $autoReturn = $issue->auto_return_id ? ReelReturn::lockForUpdate()->find($issue->auto_return_id) : null;

            if ($returnWeight > 0) {
                if ($autoReturn) {
                    $autoReturn->update([
                        'return_date'      => $data['issue_date'],
                        'remaining_weight' => $returnWeight,
                        'return_location'  => $data['return_location'] ?? null,
                        'remarks'          => !empty($data['remarks']) ? ($data['remarks'] . ' (auto return)') : 'Auto return from issue form',
                    ]);
                } else {
                    $autoReturn = ReelReturn::create([
                        'reel_id'          => $reel->id,
                        'return_date'      => $data['issue_date'],
                        'remaining_weight' => $newBalance,
                        'returned_to'      => 'stock',
                        'return_location'  => $data['return_location'] ?? null,
                        'condition'        => 'good',
                        'remarks'          => !empty($data['remarks']) ? ($data['remarks'] . ' (auto return)') : 'Auto return from issue form',
                    ]);
                    $issue->auto_return_id = $autoReturn->id;
                    $issue->save();
                }
            } elseif ($autoReturn) {
                $autoReturn->delete();
                $issue->auto_return_id = null;
                $issue->save();
            }

            event(new \App\Events\InventoryUpdated('issue_updated', $issue->load('reel.paperQuality', 'reel.supplier')));

            return $issue->load('reel.paperQuality', 'reel.supplier');
        });
    }

    protected function refreshReelStatus($reel): void
    {
        if ($reel->balance_weight <= 0) {
            $reel->status = 'fully_used';
        } elseif ($reel->balance_weight < $reel->original_weight) {
            $reel->status = 'partially_used';
        } else {
            $reel->status = 'in_stock';
        }
    }

    protected function validateAndSyncBalance($reel): bool
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
