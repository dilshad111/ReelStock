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

        $products = $query->orderBy('customer_id')->orderBy('item_name')->get();

        $data = $products->map(function ($product) use ($request) {
            $receiptQuery = FGReceipt::where('product_id', $product->id);
            $dispatchQuery = FGDispatch::where('product_id', $product->id);

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $receiptQuery->whereBetween('date', [$request->date_from, $request->date_to]);
                $dispatchQuery->whereBetween('date', [$request->date_from, $request->date_to]);
            }

            $totalProduced = $receiptQuery->sum('quantity_produced');
            $totalDispatched = $dispatchQuery->sum('quantity_dispatched');

            $lastLedger = FGStockLedger::where('product_id', $product->id)->latest('id')->first();
            $currentBalance = $lastLedger ? (float)$lastLedger->balance_after : (float)$product->opening_balance;

            return [
                'customer_id' => $product->customer_id,
                'customer_name' => $product->customer->name ?? 'Unknown',
                'product_id' => $product->id,
                'item_code' => $product->item_code,
                'item_name' => $product->item_name,
                'opening_balance' => (float)$product->opening_balance,
                'total_produced' => (float)$totalProduced,
                'total_dispatched' => (float)$totalDispatched,
                'current_balance' => $currentBalance,
            ];
        });

        // Group by customer
        $grouped = $data->groupBy('customer_name')->map(function ($items, $customer) {
            return [
                'customer' => $customer,
                'products' => $items->values(),
                'total_produced' => $items->sum('total_produced'),
                'total_dispatched' => $items->sum('total_dispatched'),
                'total_balance' => $items->sum('current_balance'),
            ];
        })->values();

        return response()->json($grouped);
    }

    /**
     * B. Job-wise Report
     */
    public function jobReport(Request $request)
    {
        $query = FGReceipt::with(['customer', 'product'])
            ->select('job_number', 'customer_id', 'product_id',
                DB::raw('SUM(quantity_produced) as total_produced'))
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
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        }

        $jobs = $query->orderBy('job_number', 'desc')->paginate(50);

        $jobs->getCollection()->transform(function ($job) {
            $totalDispatched = FGDispatch::where('job_number', $job->job_number)
                ->where('product_id', $job->product_id)
                ->sum('quantity_dispatched');

            $job->total_dispatched = (float)$totalDispatched;
            $job->remaining_balance = (float)$job->total_produced - (float)$totalDispatched;
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
     * FG Dashboard data
     */
    public function dashboard(Request $request)
    {
        $totalProducts = Product::count();
        $totalReceipts = FGReceipt::count();
        $totalDispatches = FGDispatch::count();

        $startOfMonth = now()->startOfMonth();

        $monthlyProduced = FGReceipt::where('date', '>=', $startOfMonth)->sum('quantity_produced');
        $monthlyDispatched = FGDispatch::where('date', '>=', $startOfMonth)->sum('quantity_dispatched');

        // Total current stock across all products
        $products = Product::all();
        $totalStock = 0;
        foreach ($products as $product) {
            $lastLedger = FGStockLedger::where('product_id', $product->id)->latest('id')->first();
            $totalStock += $lastLedger ? (float)$lastLedger->balance_after : (float)$product->opening_balance;
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
            ];
        })->sortByDesc('balance')->take(10)->values();

        // Monthly trend (last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthLabel = $month->format('Y-m');
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $monthlyTrend[] = [
                'month' => $monthLabel,
                'produced' => (float)FGReceipt::whereBetween('date', [$start, $end])->sum('quantity_produced'),
                'dispatched' => (float)FGDispatch::whereBetween('date', [$start, $end])->sum('quantity_dispatched'),
            ];
        }

        return response()->json([
            'kpis' => [
                'total_products' => $totalProducts,
                'total_stock' => round($totalStock, 2),
                'monthly_produced' => round($monthlyProduced, 2),
                'monthly_dispatched' => round($monthlyDispatched, 2),
            ],
            'top_products' => $topProducts,
            'monthly_trend' => $monthlyTrend,
            'last_updated' => now()->toISOString(),
        ]);
    }
}
