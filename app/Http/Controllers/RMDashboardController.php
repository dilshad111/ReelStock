<?php

namespace App\Http\Controllers;

use App\Models\RMItem;
use App\Models\RMReceiptItem;
use App\Models\RMConsumptionItem;
use App\Models\RMStockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RMDashboardController extends Controller
{
    public function index()
    {
        // 1. Total Stock Valuation
        $items = RMItem::all();
        $totalValuation = 0;
        foreach ($items as $item) {
            $lastLedger = RMStockLedger::where('rm_item_id', $item->id)->latest('id')->first();
            $balance = $lastLedger ? (float)$lastLedger->balance_after : 0;
            $totalValuation += $balance * (float)$item->cost_price;
        }

        // 2. Low Stock Items Count
        $lowStockCount = 0;
        foreach ($items as $item) {
            $lastLedger = RMStockLedger::where('rm_item_id', $item->id)->latest('id')->first();
            $balance = $lastLedger ? (float)$lastLedger->balance_after : 0;
            if ($balance < (float)$item->min_stock_alert) {
                $lowStockCount++;
            }
        }

        // 3. Monthly Consumption
        $monthlyConsumption = RMConsumptionItem::whereHas('consumption', function($q) {
            $q->whereMonth('date', now()->month)->whereYear('date', now()->year);
        })->sum('quantity');

        // 4. Monthly Receiving
        $monthlyReceiving = RMReceiptItem::whereHas('receipt', function($q) {
            $q->whereMonth('date', now()->month)->whereYear('date', now()->year);
        })->sum('quantity');

        // 5. Top Consumed Materials
        $topConsumed = RMConsumptionItem::select('rm_item_id', DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('rm_item_id')
            ->orderBy('total_qty', 'desc')
            ->with('item')
            ->limit(5)
            ->get();

        // 6. Consumption Trend (Last 6 Months)
        $consumptionTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $qty = RMConsumptionItem::whereHas('consumption', function($q) use ($date) {
                $q->whereMonth('date', $date->month)->whereYear('date', $date->year);
            })->sum('quantity');
            
            $consumptionTrend[] = [
                'month' => $date->format('M Y'),
                'quantity' => (float)$qty
            ];
        }

        return response()->json([
            'stats' => [
                'total_valuation' => $totalValuation,
                'low_stock_count' => $lowStockCount,
                'monthly_consumption' => (float)$monthlyConsumption,
                'monthly_receiving' => (float)$monthlyReceiving,
            ],
            'top_consumed' => $topConsumed,
            'consumption_trend' => $consumptionTrend
        ]);
    }
}
