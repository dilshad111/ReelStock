<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\FGStockLedger;
use App\Models\CurrentFGStock;
use App\Models\InventoryAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FGReconciliationService
{
    /**
     * Run full reconciliation report comparing document sums, ledger sums, and cache.
     *
     * @return array
     */
    public static function checkDiscrepancies(): array
    {
        $discrepancies = [];

        // Fetch products and warehouses
        $products = Product::all();
        $warehouses = Warehouse::all();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                $productId = $product->id;
                $warehouseId = $warehouse->id;

                // 1. Get cache quantity
                $cacheRecord = CurrentFGStock::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();
                $cacheQty = $cacheRecord ? (float)$cacheRecord->quantity : 0.0000;

                // 2. Get ledger quantity sum
                $ledgerSum = (float)FGStockLedger::where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->sum(DB::raw('quantity_in - quantity_out'));

                // 3. Get original document sum (for default warehouse WH001 or where warehouse matches)
                $receiptsSum = (float)DB::table('fg_receipts')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->sum('quantity_produced');

                $dispatchesSum = (float)DB::table('fg_dispatches')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->sum('quantity_dispatched');

                $damagesSum = (float)DB::table('fg_damages')
                    ->where('product_id', $productId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('status', 'posted')
                    ->sum('quantity');

                // Adjust for opening balance in calculations (if it is recorded as an 'opening' type in ledger)
                // If ledger has an 'opening' type record, that's already in the ledger sum.
                // But the raw document tables don't have the opening balance. So raw document sum needs it added.
                $docSum = (float)$product->opening_balance + $receiptsSum - $dispatchesSum - $damagesSum;

                // Check for differences
                $cacheVsLedger = abs($cacheQty - $ledgerSum);
                $ledgerVsDoc = abs($ledgerSum - $docSum);

                if ($cacheVsLedger > 0.0001 || $ledgerVsDoc > 0.0001) {
                    $discrepancies[] = [
                        'product_id' => $productId,
                        'item_code' => $product->item_code,
                        'item_name' => $product->item_name,
                        'warehouse_id' => $warehouseId,
                        'warehouse_code' => $warehouse->code,
                        'cached_qty' => $cacheQty,
                        'ledger_qty' => $ledgerSum,
                        'document_qty' => $docSum,
                        'cache_vs_ledger_diff' => $cacheQty - $ledgerSum,
                        'ledger_vs_doc_diff' => $ledgerSum - $docSum,
                    ];
                }
            }
        }

        return $discrepancies;
    }

    /**
     * Rebuild the current_fg_stock cache table from the ledger.
     * Optionally corrects ledger balance_after fields.
     *
     * @param int $userId
     * @return array
     */
    public static function rebuildCacheFromLedger(int $userId): array
    {
        return DB::transaction(function () use ($userId) {
            // Lock cache table
            CurrentFGStock::lockForUpdate()->get();

            $products = Product::all();
            $warehouses = Warehouse::all();
            $rebuiltCount = 0;
            $details = [];

            // Clear current cache
            CurrentFGStock::query()->delete();

            foreach ($products as $product) {
                foreach ($warehouses as $warehouse) {
                    $productId = $product->id;
                    $warehouseId = $warehouse->id;

                    // Fetch entries in strict chronological order
                    $ledgerEntries = FGStockLedger::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->get()
                        ->sort(function ($a, $b) {
                            if ($a->transaction_date === $b->transaction_date) {
                                $aIsAdd = $a->quantity_in > 0;
                                $bIsAdd = $b->quantity_in > 0;
                                if ($aIsAdd !== $bIsAdd) {
                                    return $aIsAdd ? -1 : 1;
                                }
                                return $a->id <=> $b->id;
                            }
                            return strcmp($a->transaction_date, $b->transaction_date);
                        });

                    $runningBalance = 0.0000;
                    $correctionsInLedger = 0;

                    foreach ($ledgerEntries as $entry) {
                        $runningBalance += (float)$entry->quantity_in;
                        $runningBalance -= (float)$entry->quantity_out;

                        // Correct balance_after in ledger if it was wrong
                        if (abs((float)$entry->balance_after - $runningBalance) > 0.0001) {
                            DB::table('fg_stock_ledger')
                                ->where('id', $entry->id)
                                ->update(['balance_after' => $runningBalance]);
                            $correctionsInLedger++;
                        }
                    }

                    // Populate cache if balance is non-zero (or insert 0 for tracked active products)
                    if ($runningBalance !== 0.0000 || $ledgerEntries->count() > 0) {
                        CurrentFGStock::create([
                            'product_id' => $productId,
                            'warehouse_id' => $warehouseId,
                            'quantity' => $runningBalance
                        ]);
                        $rebuiltCount++;
                    }

                    if ($ledgerEntries->count() > 0) {
                        $details[] = [
                            'item_code' => $product->item_code,
                            'warehouse' => $warehouse->code,
                            'final_balance' => $runningBalance,
                            'ledger_rows_processed' => $ledgerEntries->count(),
                            'ledger_corrections_made' => $correctionsInLedger
                        ];
                    }
                }
            }

            // Log rebuild action to audit trail
            InventoryAuditLog::create([
                'user_id' => $userId,
                'transaction_type' => 'rebuild',
                'document_number' => 'REBUILD-' . time(),
                'product_id' => $products->first()?->id ?? 1, // Fallback placeholder
                'warehouse_id' => $warehouses->first()?->id ?? 1, // Fallback placeholder
                'old_quantity' => 0.0,
                'new_quantity' => 0.0,
                'reason_for_change' => "Executed full inventory cache rebuild from ledger.",
                'ip_address' => request()->ip()
            ]);

            return [
                'success' => true,
                'rebuilt_count' => $rebuiltCount,
                'details' => $details
            ];
        });
    }
}
