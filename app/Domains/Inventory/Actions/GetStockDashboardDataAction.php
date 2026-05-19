<?php

namespace App\Domains\Inventory\Actions;

use App\Models\PaperQuality;
use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReceipt;
use App\Models\ReelReturn;
use Illuminate\Support\Facades\DB;

class GetStockDashboardDataAction
{
    public function execute(int $timeRange = 30): array
    {
        $startDate = now()->subDays($timeRange)->startOfDay();
        $startOfMonth = now()->startOfMonth();

        // 1. STOCK OVERVIEW (Real-time Godown Stock)
        $currentStockReels = Reel::with(['paperQuality', 'supplier', 'receipts', 'returns'])
            ->where('status', '!=', 'returned_to_supplier')
            ->where(function ($query) {
                $query->whereNull('status')
                      ->orWhere('status', '!=', 'fully_used');
            })
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('balance_weight')
                      ->where('balance_weight', '>', 0);
                })->orWhere(function ($q) {
                    $q->whereNull('balance_weight')
                      ->where('original_weight', '>', 0);
                });
            })
            ->get();

        // Current Reel Stock by Quality
        $stockByQuality = $currentStockReels->groupBy('paper_quality_id')->map(function ($group) {
            $quality = $group->first()->paperQuality;
            return [
                'name' => $quality ? $quality->quality . ' (' . $quality->gsm_range . ')' : 'Unknown',
                'count' => $group->count(),
                'weight' => $group->sum(function($r) { return $r->balance_weight ?? $r->original_weight; })
            ];
        })->values();

        // Current Reel Stock by Size / GSM
        $stockBySizeGsm = $currentStockReels->map(function ($reel) {
            $gsm = $reel->receipts->first()->gsm ?? ($reel->paperQuality->gsm_range ?? 'N/A');
            return [
                'label' => $reel->reel_size . '" / ' . $gsm . ' GSM',
                'weight' => $reel->balance_weight ?? $reel->original_weight
            ];
        })->groupBy('label')->map(function ($group, $label) {
            return [
                'label' => $label,
                'weight' => $group->sum('weight'),
                'count' => $group->count()
            ];
        })->values()->sortByDesc('weight')->take(10)->values();

        // Total Available Reel Weight
        $totalStockWeight = $currentStockReels->sum(function($r) { return $r->balance_weight ?? $r->original_weight; });

        // Stock Status: Full vs Partial
        $fullReels = $currentStockReels->filter(function($r) {
            return is_null($r->balance_weight) || $r->balance_weight == $r->original_weight;
        });
        $partialReels = $currentStockReels->filter(function($r) {
            return !is_null($r->balance_weight) && $r->balance_weight < $r->original_weight && $r->balance_weight > 0;
        });

        $stockStatusDistribution = [
            ['label' => 'Full Reels', 'value' => $fullReels->count()],
            ['label' => 'Partial Reels', 'value' => $partialReels->count()],
        ];

        // Supplier-wise Current Stock
        $stockBySupplier = $currentStockReels->groupBy('supplier_id')->map(function ($group) {
            $supplierName = $group->first()->supplier->name ?? 'Unknown';
            return [
                'supplier' => $supplierName,
                'weight' => $group->sum(function($r) { return $r->balance_weight ?? $r->original_weight; })
            ];
        })->values()->sortByDesc('weight')->values();

        // 2. RECEIVING (INWARD) ANALYSIS
        $receivedData = ReelReceipt::with(['reel.supplier', 'reel.paperQuality'])
            ->where('receiving_date', '>=', $startDate)
            ->get();

        $receivedOverTime = $receivedData->groupBy(function($r) {
            return date('Y-m-d', strtotime($r->receiving_date));
        })->map(function($group) {
            return $group->sum(function($r) { return $r->reel->original_weight; });
        });

        // 3. ISSUE / CONSUMPTION ANALYSIS
        $issuedData = ReelIssue::with(['reel.paperQuality'])
            ->where('issue_date', '>=', $startDate)
            ->get();

        $issuedOverTime = $issuedData->groupBy(function($r) {
            return date('Y-m-d', strtotime($r->issue_date));
        })->map(function($group) {
            return $group->sum('quantity_issued');
        });

        // 4. PARTIAL REEL RETURN TRACKING
        $partialReturnsOverTime = $issuedData->where('return_to_stock_weight', '>', 0)
            ->groupBy(function($r) {
                return date('Y-m-d', strtotime($r->issue_date));
            })->map(function($group) {
                return $group->count();
            });

        $topRemainingReels = $currentStockReels->sortByDesc(function($r) {
            return $r->balance_weight ?? 0;
        })->take(10)->map(function($r) {
            return [
                'reel_no' => $r->reel_no,
                'weight' => $r->balance_weight ?? $r->original_weight
            ];
        })->values();

        // 5. SUPPLIER RETURN / REJECTION TRACKING
        $supplierReturns = ReelReturn::with(['reel.supplier', 'returnToSupplier'])
            ->where('returned_to', 'supplier')
            ->where('return_date', '>=', $startDate)
            ->get();

        $supplierReturnsOverTime = $supplierReturns->groupBy(function($r) {
            return date('Y-m-d', strtotime($r->return_date));
        })->map(function($group) {
            return $group->count();
        });

        $supplierReturnWeight = $supplierReturns->groupBy(function($r) {
            return $r->returnToSupplier->name ?? 'Unknown';
        })->map(function($group) {
            return $group->sum('remaining_weight');
        });

        $returnReasons = $supplierReturns->groupBy('condition')->map(function($group) {
            return $group->count();
        });

        // 6. KPIS
        $reelsInStockCount = $currentStockReels->count();
        $reelsUsedThisMonthCount = ReelIssue::where('issue_date', '>=', $startOfMonth)->count();
        $weightUsedThisMonth = ReelIssue::where('issue_date', '>=', $startOfMonth)->sum('net_consumed_weight');

        // Location Breakdown
        $factoryCount = 0;
        $factoryWeight = 0;
        $godownCount = 0;
        $godownWeight = 0;

        foreach ($currentStockReels as $reel) {
            $latestReturn = $reel->returns
                ->where('returned_to', 'stock')
                ->sortByDesc(fn($ret) => [$ret->return_date, $ret->id])
                ->first();
            
            $location = $latestReturn ? $latestReturn->return_location : 'GoDown';
            $weight = $reel->balance_weight ?? $reel->original_weight;

            if ($location === 'Factory') {
                $factoryCount++;
                $factoryWeight += $weight;
            } else {
                $godownCount++;
                $godownWeight += $weight;
            }
        }

        return [
            'kpis' => [
                'reels_in_stock' => $reelsInStockCount,
                'weight_in_stock' => round($totalStockWeight, 2),
                'reels_used_month' => $reelsUsedThisMonthCount,
                'weight_used_month' => round($weightUsedThisMonth, 2),
            ],
            'location_breakdown' => [
                'factory' => [
                    'count' => $factoryCount,
                    'weight' => round($factoryWeight, 2),
                ],
                'godown' => [
                    'count' => $godownCount,
                    'weight' => round($godownWeight, 2),
                ]
            ],
            'stock_overview' => [
                'by_quality' => $stockByQuality,
                'by_size_gsm' => $stockBySizeGsm,
                'total_weight' => round($totalStockWeight, 2),
                'status_distribution' => $stockStatusDistribution,
                'by_supplier' => $stockBySupplier
            ],
            'receiving_analysis' => [
                'over_time' => $receivedOverTime,
            ],
            'consumption_analysis' => [
                'over_time' => $issuedOverTime,
                'by_size' => $issuedData->groupBy(fn($r) => $r->reel->reel_size)->map(fn($group) => $group->sum('quantity_issued')),
            ],
            'partial_reel_tracking' => [
                'returns_over_time' => $partialReturnsOverTime,
                'open_partials_count' => $partialReels->count(),
                'top_remaining' => $topRemainingReels
            ],
            'supplier_return_tracking' => [
                'over_time' => $supplierReturnsOverTime,
                'by_supplier' => $supplierReturnWeight,
                'reasons' => $returnReasons,
            ],
            'full_stock' => $currentStockReels,
            'last_updated' => now()->toISOString(),
        ];
    }
}
