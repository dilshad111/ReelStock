<?php

namespace App\Domains\Inventory\Actions;

use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReceipt;
use Illuminate\Support\Collection;

class GetManagementDashboardDataAction
{
    public function execute(int $timeRange = 30): array
    {
        $startDate = now()->subDays($timeRange)->startOfDay();
        $startOfMonth = now()->startOfMonth();
        $startOf12Months = now()->subMonths(11)->startOfMonth();

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
            ->where('issue_date', '>=', $startDate)
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

        // 12 Months Consumption Data (Amount & Weight)
        $consumption12MonthsData = ReelIssue::with(['reel.receipts'])
            ->where('issue_date', '>=', $startOf12Months)
            ->get()
            ->map(function ($issue) {
                return [
                    'month' => date('Y-m', strtotime($issue->issue_date)),
                    'weight' => $issue->quantity_issued,
                    'amount' => $issue->quantity_issued * ($issue->reel->receipts->first()->rate_per_kg ?? 0)
                ];
            })->groupBy('month')->map(function ($group) {
                return [
                    'amount' => $group->sum('amount'),
                    'weight' => $group->sum('weight')
                ];
            });

        $monthlyConsumption12 = [];
        for ($i = 0; $i < 12; $i++) {
            $m = now()->subMonths(11 - $i)->format('Y-m');
            $data = $consumption12MonthsData->get($m, ['amount' => 0, 'weight' => 0]);
            $monthlyConsumption12[] = [
                'month' => $m,
                'amount' => $data['amount'],
                'weight' => $data['weight']
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
                'quality_name' => $receipt->reel->paperQuality->quality ?? 'Unknown',
                'weight' => $weight,
                'amount' => $amount
            ];
        });

        // 12 Months Received Data (Amount & Weight)
        $received12MonthsData = ReelReceipt::with(['reel'])
            ->where('receiving_date', '>=', $startOf12Months)
            ->get()
            ->map(function ($receipt) {
                return [
                    'month' => date('Y-m', strtotime($receipt->receiving_date)),
                    'weight' => $receipt->reel->original_weight,
                    'amount' => $receipt->reel->original_weight * ($receipt->rate_per_kg ?? 0)
                ];
            })->groupBy('month')->map(function ($group) {
                return [
                    'amount' => $group->sum('amount'),
                    'weight' => $group->sum('weight')
                ];
            });

        $monthlyReceived12 = [];
        for ($i = 0; $i < 12; $i++) {
            $m = now()->subMonths(11 - $i)->format('Y-m');
            $data = $received12MonthsData->get($m, ['amount' => 0, 'weight' => 0]);
            $monthlyReceived12[] = [
                'month' => $m,
                'amount' => $data['amount'],
                'weight' => $data['weight']
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

        // Receiving by Supplier (Amount & Weight)
        $receivingBySupplier = $receivedWithAmounts->groupBy('supplier_name')->map(function ($group) {
            return [
                'name' => $group->first()['supplier_name'],
                'amount' => $group->sum('amount'),
                'weight' => $group->sum('weight')
            ];
        })->values()->sortByDesc('amount')->values();

        // Receiving by Quality (Amount & Weight)
        $receivingByQuality = $receivedWithAmounts->groupBy('quality_name')->map(function ($group) {
            return [
                'name' => $group->first()['quality_name'],
                'amount' => $group->sum('amount'),
                'weight' => $group->sum('weight')
            ];
        })->values()->sortByDesc('amount')->values();

        // 4. KPIS
        $kpis = [
            'total_stock_amount' => round($totalStockAmount, 2),
            'total_stock_weight' => round($totalStockWeight, 2),
            'monthly_consumption_amount' => round($monthlyConsumptionAmount, 2),
            'monthly_received_amount' => round($monthlyReceivedAmount, 2),
            'avg_rate_per_kg' => $totalStockWeight > 0 ? round($totalStockAmount / $totalStockWeight, 2) : 0,
        ];

        return [
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
                'by_supplier' => $receivingBySupplier,
                'by_quality' => $receivingByQuality,
                'monthly_12' => $monthlyReceived12,
            ],
            'last_updated' => now()->toISOString(),
        ];
    }
}
