<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaperQuality;
use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $timeRange = $request->get('range', 30);
        $startDate = now()->subDays($timeRange)->startOfDay();

        // 1. STOCK OVERVIEW (Real-time Godown Stock)
        $currentStockReels = Reel::with(['paperQuality', 'supplier'])
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

        // Current Reel Stock by Size / GSM (Aggregation)
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
        $receivedData = \App\Models\ReelReceipt::with(['reel.supplier', 'reel.paperQuality'])
            ->where('receiving_date', '>=', $startDate)
            ->get();

        $receivedOverTime = $receivedData->groupBy(function($r) {
            return date('Y-m-d', strtotime($r->receiving_date));
        })->map(function($group) {
            return $group->sum(function($r) { return $r->reel->original_weight; });
        });

        $receivedBySupplier = $receivedData->groupBy(function($r) {
            return $r->reel->supplier->name ?? 'Unknown';
        })->map(function($group) {
            return $group->sum(function($r) { return $r->reel->original_weight; });
        });

        $receivedByQuality = $receivedData->groupBy(function($r) {
            return $r->reel->paperQuality->quality ?? 'Unknown';
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

        $issuedByQuality = $issuedData->groupBy(function($r) {
            return $r->reel->paperQuality->quality ?? 'Unknown';
        })->map(function($group) {
            return $group->sum('quantity_issued');
        });

        $issuedBySize = $issuedData->groupBy(function($r) {
            return $r->reel->reel_size;
        })->map(function($group) {
            return $group->sum('quantity_issued');
        });

        $issueVsReturn = $issuedData->groupBy(function($r) {
            return date('Y-m-d', strtotime($r->issue_date));
        })->map(function($group) {
            return [
                'issued' => $group->sum('quantity_issued'),
                'returned' => $group->sum('return_to_stock_weight')
            ];
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
        $supplierReturns = \App\Models\ReelReturn::with(['reel.supplier', 'returnToSupplier'])
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

        $supplierRejectionRate = $receivedData->groupBy(function($r) {
            return $r->reel->supplier->name ?? 'Unknown';
        })->map(function($group, $supplier) use ($supplierReturns) {
            $totalReceived = $group->count();
            $totalReturned = $supplierReturns->where('returnToSupplier.name', $supplier)->count();
            return $totalReceived > 0 ? round(($totalReturned / $totalReceived) * 100, 2) : 0;
        });

        // 6. QUALITY CONTROL & TRACEABILITY
        $qualityIssueVsReturn = $issuedData->groupBy(function($r) {
            return $r->reel->paperQuality->quality ?? 'Unknown';
        })->map(function($group) {
            return [
                'issued' => $group->sum('quantity_issued'),
                'returned' => $group->sum('return_to_stock_weight')
            ];
        });

        // 7. NEW KPI SUMMARY & LOCATION BREAKDOWN
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        // 1st Card: No. of Reels available in Stock
        $reelsInStockCount = $currentStockReels->count();

        // 2nd Card: Weight (Kg) of Reels available in Stock
        // (totalStockWeight is already calculated above)

        // 3rd Card: No. of Reels Used in Current Month
        $reelsUsedThisMonthCount = ReelIssue::where('issue_date', '>=', $startOfMonth)
            ->count();

        // 4th Card: Weight (Kg) of Reels Used in Current Month
        $weightUsedThisMonth = ReelIssue::where('issue_date', '>=', $startOfMonth)
            ->sum('net_consumed_weight');

        // Alerts & Action Helpers
        $lowStockAlerts = PaperQuality::all()->map(function($quality) use ($currentStockReels) {
            $stock = $currentStockReels->where('paper_quality_id', $quality->id);
            $weight = $stock->sum(function($r) { return $r->balance_weight ?? $r->original_weight; });
            return [
                'quality' => $quality->quality,
                'weight' => $weight,
                'is_low' => $weight < 1000 // Threshold: 1000kg
            ];
        })->where('is_low', true)->values();

        $partialPendingClosure = $partialReels->count();

        $supplierReturnsGroup = $supplierReturns->where('return_date', '>=', $startOfMonth)
            ->groupBy(function($r) { return $r->returnToSupplier->name ?? 'Unknown'; })
            ->map(function($group) { return $group->count(); })
            ->sortByDesc(function($val) { return $val; });
            
        $supplierHighestReturns = $supplierReturnsGroup->keys()->first() ?? 'None';

        // Factory and Godown Breakdown
        // Logic: Check latest return for location, default to GoDown
        $reelsWithReturns = Reel::whereIn('id', $currentStockReels->pluck('id'))
            ->with(['returns' => function($q) {
                $q->where('returned_to', 'stock')->orderBy('return_date', 'desc')->orderBy('id', 'desc');
            }])->get();

        $factoryCount = 0;
        $factoryWeight = 0;
        $godownCount = 0;
        $godownWeight = 0;

        foreach ($currentStockReels as $reel) {
            $reelWithRet = $reelsWithReturns->firstWhere('id', $reel->id);
            $latestReturn = $reelWithRet ? $reelWithRet->returns->first() : null;
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

        return response()->json([
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
                'by_supplier' => $receivedBySupplier,
                'by_quality' => $receivedByQuality
            ],
            'consumption_analysis' => [
                'over_time' => $issuedOverTime,
                'by_quality' => $issuedByQuality,
                'by_size' => $issuedBySize,
                'issue_vs_return' => $issueVsReturn
            ],
            'partial_reel_tracking' => [
                'returns_over_time' => $partialReturnsOverTime,
                'open_partials_count' => $partialPendingClosure,
                'top_remaining' => $topRemainingReels
            ],
            'supplier_return_tracking' => [
                'over_time' => $supplierReturnsOverTime,
                'by_supplier' => $supplierReturnWeight,
                'reasons' => $returnReasons,
                'rejection_rate' => $supplierRejectionRate
            ],
            'quality_control' => [
                'issue_vs_return' => $qualityIssueVsReturn,
            ],
            'alerts' => [
                'low_stock' => $lowStockAlerts,
                'partial_pending' => $partialPendingClosure,
                'returned_not_adjusted' => $partialReels->count(), // Using partial reels as a proxy for unadjusted in stock
                'highest_return_supplier' => $supplierHighestReturns
            ],
            'last_updated' => now()->toISOString(),
        ]);
    }

    public function managementIndex(Request $request)
    {
        $timeRange = $request->get('range', 30);
        $startDate = now()->subDays($timeRange)->startOfDay();
        $startOfMonth = now()->startOfMonth();

        // 1. ALL REELS (Active in stock) with Receipts for Rate/Price
        $currentStockReels = Reel::with(['paperQuality', 'supplier', 'receipts'])
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

        // Available Stock Data with Amounts
        $stockWithAmounts = $currentStockReels->map(function ($reel) {
            $weight = $reel->balance_weight ?? $reel->original_weight;
            $rate = $reel->receipts->first()->rate_per_kg ?? 0;
            $amount = $weight * $rate;
            return [
                'quality_id' => $reel->paper_quality_id,
                'quality_name' => $reel->paperQuality->quality ?? 'Unknown',
                'supplier_id' => $reel->supplier_id,
                'supplier_name' => $reel->supplier->name ?? 'Unknown',
                'weight' => $weight,
                'amount' => $amount
            ];
        });

        $totalStockAmount = $stockWithAmounts->sum('amount');
        $totalStockWeight = $stockWithAmounts->sum('weight');

        // Available Amount by Quality
        $amountByQuality = $stockWithAmounts->groupBy('quality_name')->map(function ($group) {
            return [
                'name' => $group->first()['quality_name'],
                'amount' => $group->sum('amount'),
                'weight' => $group->sum('weight')
            ];
        })->values()->sortByDesc('amount')->values();

        // Available Amount by Supplier
        $amountBySupplier = $stockWithAmounts->groupBy('supplier_name')->map(function ($group) {
            return [
                'name' => $group->first()['supplier_name'],
                'amount' => $group->sum('amount'),
                'weight' => $group->sum('weight')
            ];
        })->values()->sortByDesc('amount')->values();

        // 2. CONSUMPTION ANALYSIS (Amount-based)
        $issuedData = ReelIssue::with(['reel.receipts', 'reel.paperQuality'])
            ->where('issue_date', '>=', $startDate) // For trend
            ->get();

        $consumptionWithAmounts = $issuedData->map(function ($issue) {
            $weight = $issue->quantity_issued;
            $rate = $issue->reel->receipts->first()->rate_per_kg ?? 0;
            $amount = $weight * $rate;
            return [
                'id' => $issue->id,
                'date' => date('Y-m-d', strtotime($issue->issue_date)),
                'month' => date('Y-m', strtotime($issue->issue_date)),
                'quality_name' => $issue->reel->paperQuality->quality ?? 'Unknown',
                'weight' => $weight,
                'amount' => $amount
            ];
        });

        // 12 Months Consumption Bar Data
        $startOf12Months = now()->subMonths(11)->startOfMonth();
        $consumption12MonthsData = ReelIssue::with(['reel.receipts'])
            ->where('issue_date', '>=', $startOf12Months)
            ->get()
            ->map(function ($issue) {
                return [
                    'month' => date('Y-m', strtotime($issue->issue_date)),
                    'amount' => $issue->quantity_issued * ($issue->reel->receipts->first()->rate_per_kg ?? 0)
                ];
            })->groupBy('month')->map(function ($group) {
                return $group->sum('amount');
            });

        // Fill missing months with 0
        $monthlyConsumption12 = [];
        for ($i = 0; $i < 12; $i++) {
            $m = now()->subMonths(11 - $i)->format('Y-m');
            $monthlyConsumption12[] = [
                'month' => $m,
                'amount' => $consumption12MonthsData->get($m, 0)
            ];
        }

        $monthlyConsumptionAmount = $consumptionWithAmounts->filter(function($c) use ($startOfMonth) {
            return $c['date'] >= $startOfMonth->toDateString();
        })->sum('amount');

        $consumptionOverTime = $consumptionWithAmounts->groupBy('date')->map(function ($group) {
            return [
                'date' => $group->first()['date'],
                'amount' => $group->sum('amount'),
                'weight' => $group->sum('weight')
            ];
        })->values()->sortBy('date')->values();

        $consumptionByQuality = $consumptionWithAmounts->groupBy('quality_name')->map(function ($group) {
            return [
                'name' => $group->first()['quality_name'],
                'amount' => $group->sum('amount'),
                'weight' => $group->sum('weight')
            ];
        })->values()->sortByDesc('amount')->values();

        // 3. INWARD / RECEIVING ANALYSIS (Amount-based)
        $receivedData = ReelReceipt::with(['reel.paperQuality', 'reel.supplier'])
            ->where('receiving_date', '>=', $startDate)
            ->get();

        $receivedWithAmounts = $receivedData->map(function ($receipt) {
            $weight = $receipt->reel->original_weight;
            $rate = $receipt->rate_per_kg ?? 0;
            $amount = $weight * $rate;
            return [
                'id' => $receipt->id,
                'date' => date('Y-m-d', strtotime($receipt->receiving_date)),
                'month' => date('Y-m', strtotime($receipt->receiving_date)),
                'supplier_name' => $receipt->reel->supplier->name ?? 'Unknown',
                'weight' => $weight,
                'amount' => $amount
            ];
        });

        // 12 Months Received Bar Data
        $received12MonthsData = ReelReceipt::with(['reel'])
            ->where('receiving_date', '>=', $startOf12Months)
            ->get()
            ->map(function ($receipt) {
                return [
                    'month' => date('Y-m', strtotime($receipt->receiving_date)),
                    'amount' => $receipt->reel->original_weight * ($receipt->rate_per_kg ?? 0)
                ];
            })->groupBy('month')->map(function ($group) {
                return $group->sum('amount');
            });

        $monthlyReceived12 = [];
        for ($i = 0; $i < 12; $i++) {
            $m = now()->subMonths(11 - $i)->format('Y-m');
            $monthlyReceived12[] = [
                'month' => $m,
                'amount' => $received12MonthsData->get($m, 0)
            ];
        }

        $monthlyReceivedAmount = $receivedWithAmounts->filter(function($r) use ($startOfMonth) {
            return $r['date'] >= $startOfMonth->toDateString();
        })->sum('amount');

        $receivingOverTime = $receivedWithAmounts->groupBy('date')->map(function ($group) {
            return [
                'date' => $group->first()['date'],
                'amount' => $group->sum('amount'),
                'weight' => $group->sum('weight')
            ];
        })->values()->sortBy('date')->values();

        // 4. KPIS
        $kpis = [
            'total_stock_amount' => round($totalStockAmount, 2),
            'total_stock_weight' => round($totalStockWeight, 2),
            'monthly_consumption_amount' => round($monthlyConsumptionAmount, 2),
            'monthly_received_amount' => round($monthlyReceivedAmount, 2),
            'avg_rate_per_kg' => $totalStockWeight > 0 ? round($totalStockAmount / $totalStockWeight, 2) : 0,
        ];

        return response()->json([
            'kpis' => $kpis,
            'stock' => [
                'by_quality' => $amountByQuality,
                'by_supplier' => $amountBySupplier,
            ],
            'consumption' => [
                'over_time' => $consumptionOverTime,
                'by_quality' => $consumptionByQuality,
                'monthly_12' => $monthlyConsumption12,
            ],
            'receiving' => [
                'over_time' => $receivingOverTime,
                'monthly_12' => $monthlyReceived12,
            ],
            'last_updated' => now()->toISOString(),
        ]);
    }
}
