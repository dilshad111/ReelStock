<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\FGStockLedger;
use App\Models\CurrentFGStock;
use App\Models\InventoryAuditLog;
use Illuminate\Support\Facades\DB;

class FGInventoryService
{
    /**
     * Record an inventory movement in the stock ledger and update current stock cache.
     *
     * @param string $type
     * @param int $referenceId
     * @param string $docNum
     * @param int $productId
     * @param int $warehouseId
     * @param int|null $customerId
     * @param string|null $jobNumber
     * @param float $qtyIn
     * @param float $qtyOut
     * @param string $date
     * @param int $userId
     * @param string|null $remarks
     * @return FGStockLedger
     * @throws \Exception
     */
    public static function recordMovement(
        string $type,
        int $referenceId,
        string $docNum,
        int $productId,
        int $warehouseId,
        ?int $customerId,
        ?string $jobNumber,
        float $qtyIn,
        float $qtyOut,
        string $date,
        int $userId,
        ?string $remarks = null
    ): FGStockLedger {
        // Enforce strict DB transaction
        return DB::transaction(function () use (
            $type, $referenceId, $docNum, $productId, $warehouseId,
            $customerId, $jobNumber, $qtyIn, $qtyOut, $date, $userId, $remarks
        ) {
            // 1. Verify existence of Product and Warehouse
            $product = Product::findOrFail($productId);
            $warehouse = Warehouse::findOrFail($warehouseId);

            if ($warehouse->status !== 'Active') {
                throw new \Exception("Warehouse {$warehouse->name} is not active.");
            }

            // 2. Validate quantities
            if ($qtyIn < 0 || $qtyOut < 0) {
                throw new \Exception("Transaction quantities cannot be negative values.");
            }
            if ($qtyIn === 0.0 && $qtyOut === 0.0) {
                throw new \Exception("Transaction must have either quantity in or quantity out greater than zero.");
            }

            // 3. Prevent duplicate ledger postings for the same source document
            $duplicate = FGStockLedger::where('transaction_type', $type)
                ->where('reference_id', $referenceId)
                ->exists();
            if ($duplicate) {
                throw new \Exception("Duplicate posting attempt: Ledger entry already exists for transaction type '{$type}' with ID {$referenceId}.");
            }

            // 4. Lock current stock cache row to serialize updates and prevent race conditions
            $currentStock = CurrentFGStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            $oldCacheQty = $currentStock ? (float)$currentStock->quantity : 0.0000;

            // 5. Append new entry in append-only stock ledger
            $ledgerEntry = FGStockLedger::create([
                'transaction_type' => $type,
                'reference_id' => $referenceId,
                'document_number' => $docNum,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'customer_id' => $customerId,
                'job_number' => $jobNumber,
                'quantity_in' => $qtyIn,
                'quantity_out' => $qtyOut,
                'balance_after' => 0.0000,
                'transaction_date' => $date,
                'created_by' => $userId,
                'remarks' => $remarks
            ]);

            // 6. Recalculate running balances from this date onwards (supports standard & backdated entries)
            $prev = FGStockLedger::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('transaction_date', '<', $date)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('id', 'desc')
                ->first();

            $runningBalance = $prev ? (float)$prev->balance_after : 0.0000;

            // Load and sort subsequent entries chronologically
            $entriesToRecalculate = FGStockLedger::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->where('transaction_date', '>=', $date)
                ->get()
                ->sort(function ($a, $b) {
                    if ($a->transaction_date === $b->transaction_date) {
                        // Receipts/additions must sort before dispatches/reductions
                        $aIsAdd = $a->quantity_in > 0;
                        $bIsAdd = $b->quantity_in > 0;
                        if ($aIsAdd !== $bIsAdd) {
                            return $aIsAdd ? -1 : 1;
                        }
                        return $a->id <=> $b->id;
                    }
                    return strcmp($a->transaction_date, $b->transaction_date);
                });

            foreach ($entriesToRecalculate as $entry) {
                $runningBalance += (float)$entry->quantity_in;
                $runningBalance -= (float)$entry->quantity_out;

                if ($runningBalance < 0 && !$warehouse->allow_negative_stock) {
                    throw new \Exception(
                        "Insufficient stock. Transaction would drive running stock balance negative ({$runningBalance}) for product {$product->item_code} on {$entry->transaction_date}."
                    );
                }

                // Update the balance_after for this row
                DB::table('fg_stock_ledger')
                    ->where('id', $entry->id)
                    ->update(['balance_after' => $runningBalance]);
            }

            // 7. Update current stock cache table
            CurrentFGStock::updateOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity' => $runningBalance]
            );

            // 8. Log to Audit Trail
            InventoryAuditLog::create([
                'user_id' => $userId,
                'transaction_type' => $type,
                'document_number' => $docNum,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'old_quantity' => $oldCacheQty,
                'new_quantity' => $runningBalance,
                'reason_for_change' => $remarks ?: "Posted {$type} transaction.",
                'ip_address' => request()->ip()
            ]);

            return $ledgerEntry;
        });
    }
}
