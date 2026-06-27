<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\FGReceipt;
use App\Models\FGDispatch;
use App\Models\FGStockLedger;
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

        $data = $products->map(function ($product) use ($request) {
            // 1. Calculate opening balance as of date_from (or opening_balance of product)
            $openingBalance = (float)$product->opening_balance;
            if ($request->filled('date_from')) {
                $openingLedger = FGStockLedger::where('product_id', $product->id)
                    ->where('transaction_date', '<', $request->date_from)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($openingLedger) {
                    $openingBalance = (float)$openingLedger->balance_after;
                }
            }

            // 2. Sum quantity_in and quantity_out within the date range from the ledger
            $ledgerQuery = FGStockLedger::where('product_id', $product->id);
            if ($request->filled('date_from')) {
                $ledgerQuery->where('transaction_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $ledgerQuery->where('transaction_date', '<=', $request->date_to);
            }

            $totalProduced = (float)$ledgerQuery->sum('quantity_in');
            $totalDispatched = (float)$ledgerQuery->sum('quantity_out');

            // 3. Closing balance: openingBalance + totalProduced - totalDispatched
            $currentBalance = $openingBalance + $totalProduced - $totalDispatched;

            return [
                'customer_id' => $product->customer_id,
                'customer_name' => $product->customer->name ?? 'Unknown',
                'product_id' => $product->id,
                'item_code' => $product->item_code,
                'item_name' => $product->item_name,
                'opening_balance' => $openingBalance,
                'total_produced' => $totalProduced,
                'total_dispatched' => $totalDispatched,
                'current_balance' => $currentBalance,
                'rate' => (float)$product->rate,
                'amount' => (float)($currentBalance * $product->rate)
            ];
        })->filter(function ($item) {
            // Keep items with either stock transactions or non-zero balance in the period
            return $item['current_balance'] > 0 || $item['total_produced'] > 0 || $item['total_dispatched'] > 0 || $item['opening_balance'] > 0;
        })->values();

        // Group by customer
        $grouped = $data->groupBy('customer_name')->map(function ($items, $customer) {
            return [
                'customer' => $customer,
                'products' => $items->values(),
                'total_produced' => $items->sum('total_produced'),
                'total_dispatched' => $items->sum('total_dispatched'),
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

        if ($request->filled('date_from')) {
            $dispatchSql .= " AND fg_dispatches.date >= '" . $request->date_from . "'";
        }
        if ($request->filled('date_to')) {
            $dispatchSql .= " AND fg_dispatches.date <= '" . $request->date_to . "'";
        }

        $query = FGReceipt::with(['customer', 'product'])
            ->select('job_number', 'customer_id', 'product_id',
                DB::raw('SUM(quantity_produced) as total_produced'),
                DB::raw('(' . $dispatchSql . ') as total_dispatched'))
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
        $query->havingRaw('total_produced > IFNULL(total_dispatched, 0)');

        $jobs = $query->orderBy('job_number', 'desc')->paginate(50);

        $jobs->getCollection()->transform(function ($job) {
            $job->total_dispatched = (float)($job->total_dispatched ?? 0);
            $job->total_produced = (float)$job->total_produced;
            $job->remaining_balance = $job->total_produced - $job->total_dispatched;
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

        $totalProduced = $receipts->sum('quantity_produced');
        $totalDispatched = $dispatches->sum('quantity_dispatched');

        // Calculate running balance for dispatches
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
            'total_produced' => (float)$totalProduced,
            'total_dispatched' => (float)$totalDispatched,
            'remaining_balance' => (float)$totalProduced - (float)$totalDispatched,
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
            $lastLedger = FGStockLedger::where('product_id', $product->id)->latest('id')->first();
            $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : (float)$product->opening_balance;

            return [
                'customer_name' => $product->customer->name ?? 'Unknown',
                'item_code' => $product->item_code,
                'item_name' => $product->item_name,
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

        $startOfMonth = now()->startOfMonth();

        $monthlyProducedQuery = FGReceipt::where('date', '>=', $startOfMonth);
        $monthlyDispatchedQuery = FGDispatch::where('date', '>=', $startOfMonth);

        $monthlyProduced = (float)$monthlyProducedQuery->sum('quantity_produced');
        $monthlyDispatched = (float)$monthlyDispatchedQuery->sum('quantity_dispatched');

        // Total current stock across all products
        $products = Product::all();
        $totalStock = 0;
        $totalStockAmount = 0;
        foreach ($products as $product) {
            $lastLedger = FGStockLedger::where('product_id', $product->id)->latest('id')->first();
            $balance = $lastLedger ? (float)$lastLedger->balance_after : (float)$product->opening_balance;
            $totalStock += $balance;
            $totalStockAmount += ($balance * (float)$product->rate);
        }

        // Top 10 products by stock
        $topProducts = $products->map(function ($p) {
            $lastLedger = FGStockLedger::where('product_id', $p->id)->latest('id')->first();
            $balance = $lastLedger ? (float)$lastLedger->balance_after : (float)$p->opening_balance;
            return [
                'id' => $p->id,
                'item_name' => $p->item_name,
                'item_code' => $p->item_code,
                'balance' => $balance,
                'amount' => $balance * (float)$p->rate,
            ];
        })->sortByDesc('balance')->take(10)->values();

        // Monthly trend (last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthLabel = $month->format('Y-m');
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $receipts = FGReceipt::whereBetween('date', [$start, $end])->get();
            $dispatches = FGDispatch::whereBetween('date', [$start, $end])->get();

            $prodQty = (float)$receipts->sum('quantity_produced');
            $dispQty = (float)$dispatches->sum('quantity_dispatched');

            // Calculate amounts - need to join with products to get rates
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
            ],
            'top_products' => $topProducts,
            'monthly_trend' => $monthlyTrend,
            'last_updated' => now()->toISOString(),
        ]);
    }
}
