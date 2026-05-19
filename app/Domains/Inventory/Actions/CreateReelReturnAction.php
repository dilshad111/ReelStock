<?php

namespace App\Domains\Inventory\Actions;

use App\Models\Reel;
use App\Models\ReelReturn;
use App\Domains\Inventory\DTOs\ReelReturnDTO;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class CreateReelReturnAction
{
    public function execute(ReelReturnDTO $dto): ReelReturn
    {
        return DB::transaction(function () use ($dto) {
            $reel = Reel::where('reel_no', $dto->reelNo)->lockForUpdate()->first();

            if (!$reel) {
                throw new Exception('Reel not found', 404);
            }

            $latestIssue = $reel->issues()->orderByDesc('issue_date')->orderByDesc('created_at')->first();
            $latestStockReturn = $reel->returns()->where('returned_to', 'stock')->orderByDesc('return_date')->orderByDesc('created_at')->first();

            $hasIssueHistory = $latestIssue !== null;
            $alreadyInStock = !$hasIssueHistory;

            if ($hasIssueHistory && $latestStockReturn) {
                $issueDate = $latestIssue->issue_date ? Carbon::parse($latestIssue->issue_date) : null;
                $issueCreated = $latestIssue->created_at;
                $returnDate = $latestStockReturn->return_date ? Carbon::parse($latestStockReturn->return_date) : null;
                $returnCreated = $latestStockReturn->created_at;

                if ($issueDate && $returnDate) {
                    if ($returnDate->gt($issueDate)) {
                        $alreadyInStock = true;
                    } elseif ($returnDate->eq($issueDate)) {
                        if ($returnCreated && $issueCreated && $returnCreated->gte($issueCreated)) {
                            $alreadyInStock = true;
                        }
                    } else {
                        $alreadyInStock = false;
                    }
                } elseif (!$issueDate && $returnDate) {
                    $alreadyInStock = true;
                } elseif ($issueDate && !$returnDate) {
                    $alreadyInStock = false;
                } elseif ($returnCreated && $issueCreated) {
                    $alreadyInStock = $returnCreated->gte($issueCreated);
                }

                if (!$alreadyInStock) {
                    $hasNewIssueAfterReturn = $reel->issues()
                        ->where(function ($query) use ($latestStockReturn) {
                            $query->where('issue_date', '>', $latestStockReturn->return_date)
                                  ->orWhere(function ($inner) use ($latestStockReturn) {
                                      $inner->where('issue_date', $latestStockReturn->return_date)
                                            ->where('created_at', '>', $latestStockReturn->created_at);
                                  });
                        })
                        ->exists();
                    $alreadyInStock = !$hasNewIssueAfterReturn;
                }
            }

            if ($dto->remainingWeight > $reel->original_weight) {
                throw new Exception('Remaining weight cannot exceed original weight', 400);
            }

            if ($dto->returnedTo === 'supplier' && $dto->remainingWeight > $reel->balance_weight) {
                throw new Exception('Returned weight cannot exceed current balance weight', 400);
            }

            if ($dto->returnedTo === 'stock' && (!$hasIssueHistory || $alreadyInStock)) {
                throw new Exception('This reel is already in the stock.', 400);
            }

            $challanNo = null;
            if ($dto->returnedTo === 'supplier') {
                if ($dto->challanNo) {
                    $challanNo = $dto->challanNo;
                } else {
                    $challanNo = 'RT' . str_pad($this->generateNextChallanSequence(), 4, '0', STR_PAD_LEFT);
                }
            }

            $return = ReelReturn::create([
                'reel_id'               => $reel->id,
                'challan_no'            => $challanNo,
                'return_date'           => $dto->returnDate,
                'remaining_weight'      => $dto->remainingWeight,
                'returned_to'           => $dto->returnedTo,
                'return_location'       => $dto->returnLocation,
                'condition'             => $dto->condition,
                'vehicle_number'        => $dto->vehicleNumber,
                'return_to_supplier_id' => $dto->returnToSupplierId,
                'remarks'               => $dto->remarks,
            ]);

            if ($dto->returnedTo === 'stock') {
                $reel->balance_weight = $dto->remainingWeight;
            } else {
                $reel->balance_weight = max($reel->balance_weight - $dto->remainingWeight, 0);
            }

            $this->updateReelStatus($reel);
            
            $this->validateAndSyncBalance($reel);
            $reel->save();

            event(new \App\Events\InventoryUpdated('return_created', $return->load('reel')));

            return $return;
        });
    }

    protected function generateNextChallanSequence(): int
    {
        $challans = ReelReturn::whereNotNull('challan_no')->pluck('challan_no');
        $max = 0;
        foreach ($challans as $challan) {
            $sequence = $this->parseChallanSequence($challan);
            if ($sequence > $max) {
                $max = $sequence;
            }
        }
        return $max + 1;
    }

    protected function parseChallanSequence(?string $challanNo): int
    {
        if (!$challanNo) return 0;
        if (preg_match('/^RT(\d+)$/', strtoupper($challanNo), $matches)) {
            return (int) $matches[1];
        }
        return 0;
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
