<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\FGReceipt;
use App\Models\FGDispatch;
use App\Models\FGStockLedger;
use App\Models\CurrentFGStock;
use App\Services\FGReconciliationService;
use Illuminate\Support\Facades\DB;

class FGReportController extends Controller
{
    /**
     * A. Finished Goods Stock Report
     * Grouped by Customer → Product with totals
     */
    public function stockReport(Request $request)
    {
        $query = Product::with('customer');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('item_search')) {
            $search = $request->item_search;
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('customer_id')->orderBy('item_name')->get();
        $warehouseId = $request->input('warehouse_id');

        $data = $products->map(function ($product) use ($request, $warehouseId) {
            $totalProduced = 0.0;
            $totalDispatched = 0.0;
            $totalDamaged = 0.0;

            if ($request->filled('date_from') || $request->filled('date_to')) {
                // 1. Calculate opening balance as of date_from segmenting by warehouse
                $openingBalance = 0.0;
                if (!$warehouseId || (int)$warehouseId === 1) {
                    $openingBalance = (float)$product->opening_balance;
                }

                if ($request->filled('date_from')) {
                    $openingLedgerQuery = FGStockLedger::where('product_id', $product->id)
                        ->where('transaction_date', '<', $request->date_from);
                    if ($warehouseId) {
                        $openingLedgerQuery->where('warehouse_id', $warehouseId);
                    }
                    $openingLedger = $openingLedgerQuery->orderBy('transaction_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                    if ($openingLedger) {
                        $openingBalance = (float)$openingLedger->balance_after;
                    }
                }

                // 2. Sum quantities within the date range from the ledger
                $ledgerQuery = FGStockLedger::where('product_id', $product->id);
                if ($warehouseId) {
                    $ledgerQuery->where('warehouse_id', $warehouseId);
                }
                if ($request->filled('date_from')) {
                    $ledgerQuery->where('transaction_date', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $ledgerQuery->where('transaction_date', '<=', $request->date_to);
                }

                $prodIn = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['receipt', 'receipt_reversal'])->sum('quantity_in');
                $prodOut = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['receipt', 'receipt_reversal'])->sum('quantity_out');
                $totalProduced = $prodIn - $prodOut;

                $dispOut = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['dispatch', 'dispatch_reversal'])->sum('quantity_out');
                $dispIn = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['dispatch', 'dispatch_reversal'])->sum('quantity_in');
                $totalDispatched = $dispOut - $dispIn;

                $dmgOut = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['damage', 'damage_reversal'])->sum('quantity_out');
                $dmgIn = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['damage', 'damage_reversal'])->sum('quantity_in');
                $totalDamaged = $dmgOut - $dmgIn;

                $currentBalance = $openingBalance + $totalProduced - $totalDispatched - $totalDamaged;
            } else {
                // Read directly from current stock cache
                $stockQuery = CurrentFGStock::where('product_id', $product->id);
                if ($warehouseId) {
                    $stockQuery->where('warehouse_id', $warehouseId);
                }
                $currentBalance = (float)$stockQuery->sum('quantity');
                
                // For default report totals without date filters, sum history for these totals
                $ledgerQuery = FGStockLedger::where('product_id', $product->id);
                if ($warehouseId) {
                    $ledgerQuery->where('warehouse_id', $warehouseId);
                }
                
                $prodIn = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['receipt', 'receipt_reversal'])->sum('quantity_in');
                $prodOut = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['receipt', 'receipt_reversal'])->sum('quantity_out');
                $totalProduced = $prodIn - $prodOut;

                $dispOut = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['dispatch', 'dispatch_reversal'])->sum('quantity_out');
                $dispIn = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['dispatch', 'dispatch_reversal'])->sum('quantity_in');
                $totalDispatched = $dispOut - $dispIn;

                $dmgOut = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['damage', 'damage_reversal'])->sum('quantity_out');
                $dmgIn = (float)$ledgerQuery->clone()->whereIn('transaction_type', ['damage', 'damage_reversal'])->sum('quantity_in');
                $totalDamaged = $dmgOut - $dmgIn;
                
                $openingBalance = 0.0;
                if (!$warehouseId || (int)$warehouseId === 1) {
                    $openingBalance = (float)$product->opening_balance;
                }
            }

            return [
                'customer_id' => $product->customer_id,
                'customer_name' => $product->customer->name ?? 'Unknown',
                'product_id' => $product->id,
                'item_code' => $product->item_code,
                'item_name' => $product->item_name,
                'opening_balance' => $openingBalance,
                'total_produced' => $totalProduced,
                'total_dispatched' => $totalDispatched,
                'total_damaged' => $totalDamaged,
                'current_balance' => $currentBalance,
                'rate' => (float)$product->rate,
                'amount' => (float)($currentBalance * $product->rate)
            ];
        })->filter(function ($item) {
            return $item['current_balance'] > 0 || $item['total_produced'] > 0 || $item['total_dispatched'] > 0 || $item['total_damaged'] > 0 || $item['opening_balance'] > 0;
        })->values();

        // Group by customer
        $grouped = $data->groupBy('customer_name')->map(function ($items, $customer) {
            return [
                'customer' => $customer,
                'products' => $items->values(),
                'total_produced' => $items->sum('total_produced'),
                'total_dispatched' => $items->sum('total_dispatched'),
                'total_damaged' => $items->sum('total_damaged'),
                'total_balance' => $items->sum('current_balance'),
                'total_amount' => $items->sum('amount'),
            ];
        })->values();

        return response()->json($grouped);
    }

    /**
     * B. Job-wise Report
     */
    public function jobReport(Request $request)
    {
        $dispatchSql = 'SELECT SUM(quantity_dispatched) FROM fg_dispatches 
                        WHERE fg_dispatches.job_number = fg_receipts.job_number 
                        AND fg_dispatches.product_id = fg_receipts.product_id';

        $damageSql = "SELECT SUM(quantity) FROM fg_damages 
                      WHERE fg_damages.job_number = fg_receipts.job_number 
                      AND fg_damages.product_id = fg_receipts.product_id
                      AND fg_damages.status = 'posted'";

        if ($request->filled('date_from')) {
            $dispatchSql .= " AND fg_dispatches.date >= '" . $request->date_from . "'";
            $damageSql .= " AND fg_damages.date >= '" . $request->date_from . "'";
        }
        if ($request->filled('date_to')) {
            $dispatchSql .= " AND fg_dispatches.date <= '" . $request->date_to . "'";
            $damageSql .= " AND fg_damages.date <= '" . $request->date_to . "'";
        }

        $query = FGReceipt::with(['customer', 'product'])
            ->select('job_number', 'customer_id', 'product_id',
                DB::raw('SUM(quantity_produced) as total_produced'),
                DB::raw('SUM(wastage) as total_wastage'),
                DB::raw('(' . $dispatchSql . ') as total_dispatched'),
                DB::raw('IFNULL((' . $damageSql . '), 0) as total_damaged'))
            ->groupBy('job_number', 'customer_id', 'product_id');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('job_number')) {
            $query->where('job_number', 'like', '%' . $request->job_number . '%');
        }
        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Only show jobs with remaining balance > 0
        $query->havingRaw('total_produced > IFNULL(total_dispatched, 0) + total_damaged');

        $jobs = $query->orderBy('job_number', 'desc')->paginate(50);

        $jobs->getCollection()->transform(function ($job) {
            $job->total_dispatched = (float)($job->total_dispatched ?? 0);
            $job->total_damaged = (float)($job->total_damaged ?? 0);
            $job->total_wastage = (float)($job->total_wastage ?? 0);
            $job->total_produced = (float)$job->total_produced;
            $job->remaining_balance = $job->total_produced - $job->total_dispatched - $job->total_damaged;
            return $job;
        });

        return response()->json($jobs);
    }

    /**
     * C. Job Detail Drill-down
     */
    public function jobDetail(Request $request)
    {
        $request->validate([
            'job_number' => 'required|string',
            'product_id' => 'required|exists:products,id',
        ]);

        $jobNumber = $request->job_number;
        $productId = $request->product_id;

        $receipts = FGReceipt::with(['customer', 'product'])
            ->where('job_number', $jobNumber)
            ->where('product_id', $productId)
            ->orderBy('date')
            ->get();

        $dispatches = FGDispatch::with(['customer', 'product'])
            ->where('job_number', $jobNumber)
            ->where('product_id', $productId)
            ->orderBy('date')
            ->get();

        $damages = \App\Models\FGDamage::with(['customer', 'product'])
            ->where('job_number', $jobNumber)
            ->where('product_id', $productId)
            ->where('status', 'posted')
            ->orderBy('date')
            ->get();

        $totalProduced = $receipts->sum('quantity_produced');
        $totalDispatched = $dispatches->sum('quantity_dispatched');
        $totalDamaged = $damages->sum('quantity');

        // Calculate running balance for dispatches & damages
        $runningBalance = (float)$totalProduced;
        $dispatchesWithBalance = $dispatches->map(function ($d) use (&$runningBalance) {
            $runningBalance -= (float)$d->quantity_dispatched;
            return [
                'id' => $d->id,
                'date' => $d->date,
                'dc_number' => $d->dc_number,
                'quantity_dispatched' => (float)$d->quantity_dispatched,
                'running_balance' => $runningBalance,
                'remarks' => $d->remarks,
            ];
        });

        return response()->json([
            'job_number' => $jobNumber,
            'product' => $receipts->first()?->product,
            'customer' => $receipts->first()?->customer,
            'receipts' => $receipts,
            'dispatches' => $dispatchesWithBalance,
            'damages' => $damages,
            'total_produced' => (float)$totalProduced,
            'total_dispatched' => (float)$totalDispatched,
            'total_damaged' => (float)$totalDamaged,
            'remaining_balance' => (float)$totalProduced - (float)$totalDispatched - (float)$totalDamaged,
        ]);
    }

    /**
     * D. Inventory Audit Report (from fg_stock_ledger)
     */
    public function auditReport(Request $request)
    {
        $query = FGStockLedger::with(['product', 'customer']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->filled('item_search')) {
            $search = $request->item_search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('transaction_date', [$request->date_from, $request->date_to]);
        }

        return response()->json(
            $query->orderBy('id', 'desc')->paginate(50)
        );
    }

    /**
     * Filter options for reports
     */
    public function getFilters()
    {
        return response()->json([
            'customers' => \App\Models\Customer::orderBy('name')->get(['id', 'name']),
            'products' => Product::with('customer:id,name')->orderBy('item_name')->get(['id', 'customer_id', 'item_code', 'item_name']),
            'warehouses' => \App\Models\Warehouse::orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * E. Inventory Email Report
     * List of items with balance > 0
     */
    public function inventoryEmailReport(Request $request)
    {
        $query = Product::with('customer');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('item_search')) {
            $search = $request->item_search;
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('item_name')->get();

        $data = $products->map(function ($product) {
            $currentBalance = (float)CurrentFGStock::where('product_id', $product->id)->sum('quantity');

            $ledger = FGStockLedger::where('product_id', $product->id);
            $prodIn = (float)$ledger->clone()->whereIn('transaction_type', ['receipt', 'receipt_reversal'])->sum('quantity_in');
            $prodOut = (float)$ledger->clone()->whereIn('transaction_type', ['receipt', 'receipt_reversal'])->sum('quantity_out');
            $produced = $prodIn - $prodOut;

            $dispOut = (float)$ledger->clone()->whereIn('transaction_type', ['dispatch', 'dispatch_reversal'])->sum('quantity_out');
            $dispIn = (float)$ledger->clone()->whereIn('transaction_type', ['dispatch', 'dispatch_reversal'])->sum('quantity_in');
            $dispatched = $dispOut - $dispIn;

            $dmgOut = (float)$ledger->clone()->whereIn('transaction_type', ['damage', 'damage_reversal'])->sum('quantity_out');
            $dmgIn = (float)$ledger->clone()->whereIn('transaction_type', ['damage', 'damage_reversal'])->sum('quantity_in');
            $damaged = $dmgOut - $dmgIn;

            return [
                'product_id' => $product->id,
                'customer_name' => $product->customer->name ?? 'Unknown',
                'item_code' => $product->item_code,
                'item_name' => $product->item_name,
                'produced' => $produced,
                'dispatched' => $dispatched,
                'damaged' => $damaged,
                'quantity' => $currentBalance,
                'rate' => (float)$product->rate,
                'amount' => (float)($currentBalance * $product->rate)
            ];
        })->filter(function ($item) {
            return $item['quantity'] > 0;
        })->values();

        return response()->json($data);
    }

    /**
     * FG Dashboard data
     */
    public function dashboard(Request $request)
    {
        $totalProducts = Product::count();
        $totalReceipts = FGReceipt::count();
        $totalDispatches = FGDispatch::count();
        $totalDamages = \App\Models\FGDamage::where('status', 'posted')->count();

        $startOfMonth = now()->startOfMonth();
        $today = now()->toDateString();

        $monthlyProducedQuery = FGReceipt::where('date', '>=', $startOfMonth);
        $monthlyDispatchedQuery = FGDispatch::where('date', '>=', $startOfMonth);

        $monthlyProduced = (float)$monthlyProducedQuery->sum('quantity_produced');
        $monthlyDispatched = (float)$monthlyDispatchedQuery->sum('quantity_dispatched');

        $todayDamage = (float)\App\Models\FGDamage::where('status', 'posted')->where('date', $today)->sum('quantity');
        $monthlyDamage = (float)\App\Models\FGDamage::where('status', 'posted')->where('date', '>=', $startOfMonth)->sum('quantity');
        $totalDamage = (float)\App\Models\FGDamage::where('status', 'posted')->sum('quantity');

        // Total current stock across all products
        $products = Product::all();
        $stockMap = CurrentFGStock::select('product_id', DB::raw('SUM(quantity) as qty'))
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $totalStock = 0;
        $totalStockAmount = 0;
        foreach ($products as $product) {
            $balance = (float)($stockMap[$product->id] ?? 0.0000);
            $totalStock += $balance;
            $totalStockAmount += ($balance * (float)$product->rate);
        }

        // Top 10 products by stock
        $topProducts = $products->map(function ($p) use ($stockMap) {
            $balance = (float)($stockMap[$p->id] ?? 0.0000);
            return [
                'id' => $p->id,
                'item_name' => $p->item_name,
                'item_code' => $p->item_code,
                'balance' => $balance,
                'amount' => $balance * (float)$p->rate,
            ];
        })->sortByDesc('balance')->take(10)->values();

        // Top Damaged Products list
        $topDamagedProducts = \App\Models\FGDamage::where('status', 'posted')
            ->select('product_id', DB::raw('SUM(quantity) as qty'))
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(function ($dmg) {
                $p = Product::find($dmg->product_id);
                return [
                    'item_name' => $p->item_name ?? 'Unknown',
                    'item_code' => $p->item_code ?? '',
                    'quantity' => (float)$dmg->qty,
                ];
            });

        // Monthly trend (last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthLabel = $month->format('Y-m');
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $receipts = FGReceipt::whereBetween('date', [$start, $end])->get();
            $dispatches = FGDispatch::whereBetween('date', [$start, $end])->get();
            $damages = \App\Models\FGDamage::where('status', 'posted')->whereBetween('date', [$start, $end])->get();

            $prodQty = (float)$receipts->sum('quantity_produced');
            $dispQty = (float)$dispatches->sum('quantity_dispatched');
            $dmgQty = (float)$damages->sum('quantity');

            // Calculate amounts
            $prodAmount = 0;
            foreach ($receipts as $r) {
                $prodAmount += ($r->quantity_produced * (float)($r->product->rate ?? 0));
            }

            $dispAmount = 0;
            foreach ($dispatches as $d) {
                $dispAmount += (float)($d->dispatch_amount ?? ((float)$d->quantity_dispatched * (float)($d->product->rate ?? 0)));
            }

            $monthlyTrend[] = [
                'month' => $monthLabel,
                'produced' => $prodQty,
                'dispatched' => $dispQty,
                'damaged' => $dmgQty,
                'produced_amount' => $prodAmount,
                'dispatched_amount' => $dispAmount,
            ];
        }

        return response()->json([
            'kpis' => [
                'total_products' => $totalProducts,
                'total_stock' => round($totalStock, 2),
                'total_stock_amount' => round($totalStockAmount, 2),
                'monthly_produced' => round($monthlyProduced, 2),
                'monthly_dispatched' => round($monthlyDispatched, 2),
                'today_damage' => round($todayDamage, 2),
                'monthly_damage' => round($monthlyDamage, 2),
                'total_damage' => round($totalDamage, 2),
            ],
            'top_products' => $topProducts,
            'top_damaged_products' => $topDamagedProducts,
            'monthly_trend' => $monthlyTrend,
            'last_updated' => now()->toISOString(),
        ]);
    }

    /**
     * Check for discrepancies between documents, ledger, and cache
     */
    public function checkReconciliation()
    {
        try {
            $discrepancies = FGReconciliationService::checkDiscrepancies();
            return response()->json([
                'success' => true,
                'total_discrepancies' => count($discrepancies),
                'discrepancies' => $discrepancies
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Rebuild cache table and correct running ledger balances
     */
    public function rebuildCache(Request $request)
    {
        try {
            $user = $request->user();
            $result = FGReconciliationService::rebuildCacheFromLedger($user ? $user->id : 1);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
