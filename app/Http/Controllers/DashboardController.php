<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;
use App\Models\ReelIssue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Fetch real data from database
        $reels = \App\Models\Reel::with('paperQuality', 'supplier')->get();

        // Calculate totals
        $totalReels = $reels->count();
        $totalOriginalWeight = $reels->sum('original_weight');
        $totalBalanceWeight = $reels->sum(function ($reel) {
            return $reel->balance_weight ?? $reel->original_weight;
        });
        $totalConsumedWeight = $reels->sum(function ($reel) {
            $balance = $reel->balance_weight ?? $reel->original_weight;
            return $reel->original_weight - $balance;
        });

        // Aggregate stock by quality
        $stockByQuality = [];
        foreach ($reels->groupBy('paper_quality_id') as $qualityId => $qualityReels) {
            $quality = $qualityReels->first()->paperQuality;
            if ($quality) {
                $stockByQuality[] = [
                    'quality' => $quality->quality . ' (' . $quality->gsm_range . ')',
                    'count' => $qualityReels->count(),
                    'weight' => $qualityReels->sum(function ($reel) {
                        return $reel->balance_weight ?? $reel->original_weight;
                    }),
                    'original_weight' => $qualityReels->sum('original_weight'),
                    'consumed_weight' => $qualityReels->sum(function ($reel) {
                        $balance = $reel->balance_weight ?? $reel->original_weight;
                        return $reel->original_weight - $balance;
                    })
                ];
            }
        }

        // Calculate efficiency
        $efficiency = $totalConsumedWeight > 0 ? ($totalConsumedWeight / $totalOriginalWeight) * 100 : 0;

        // Get recent supplier updates (last 5 receipts)
        $recentReceipts = \App\Models\ReelReceipt::with('reel.supplier', 'reel.paperQuality')
            ->orderBy('receiving_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($receipt) {
                return [
                    'name' => $receipt->reel->supplier->name ?? 'Unknown',
                    'quality' => $receipt->reel->paperQuality->quality ?? 'Unknown',
                    'receiving_date' => $receipt->receiving_date,
                    'original_weight' => $receipt->reel->original_weight
                ];
            });

        // Check for low stock alerts (reels with balance weight < 50kg)
        $lowStockReels = $reels->filter(function ($reel) {
            return ($reel->balance_weight ?? $reel->original_weight) < 50;
        })->groupBy('paper_quality_id');

        $lowStockAlerts = [];
        foreach ($lowStockReels as $qualityId => $qualityReels) {
            $quality = $qualityReels->first()->paperQuality;
            if ($quality) {
                $lowStockAlerts[] = [
                    'quality' => $quality->quality . ' (' . $quality->gsm_range . ')',
                    'count' => $qualityReels->count(),
                    'status' => 'Low Stock'
                ];
            }
        }

        // Sample consumption data for charts (last 7 days)
        $consumptionData = [];
        $qualities = $stockByQuality;
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            foreach ($qualities as $quality) {
                $consumptionData[] = [
                    'date' => $date,
                    'quality' => $quality['quality'],
                    'total_consumed' => rand(10, 50)
                ];
            }
        }

        // Sample monthly consumption
        $monthlyData = [];
        for ($i = 2; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $monthlyData[] = [
                'month' => $month,
                'total_consumed' => rand(1000, 1500)
            ];
        }

        // Sample stock movement
        $movementData = [];
        for ($i = 6; $i >= 0; $i--) {
            $movementData[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'added_weight' => rand(-100, 200)
            ];
        }

        return response()->json([
            'stock_by_quality' => $stockByQuality,
            'total_reels_in_stock' => $totalReels,
            'total_weight_in_stock' => $totalBalanceWeight,
            'total_original_weight' => $totalOriginalWeight,
            'total_consumed_weight' => $totalConsumedWeight,
            'efficiency_percentage' => round($efficiency, 1),
            'supplier_updates' => $recentReceipts,
            'low_stock_alerts' => $lowStockAlerts,
            'consumption_data' => $consumptionData,
            'monthly_consumption' => $monthlyData,
            'stock_movement' => $movementData,
            'last_updated' => now()->toISOString(),
        ]);
    }
}
