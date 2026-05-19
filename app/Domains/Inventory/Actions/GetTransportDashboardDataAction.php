<?php

namespace App\Domains\Inventory\Actions;

use App\Models\CartageBill;
use App\Models\CartageEntry;
use Illuminate\Support\Collection;

class GetTransportDashboardDataAction
{
    public function execute(int $timeRange = 30): array
    {
        $startDate = now()->subDays($timeRange)->startOfDay();

        // 1. BILLS SUMMARY
        $bills = CartageBill::with('transporter')
            ->where('bill_date', '>=', $startDate)
            ->get();

        $totalBillsCount = $bills->count();
        $totalBillAmount = $bills->sum('total_amount');
        
        $pendingBills = CartageBill::where('status', 'pending')->count();
        $approvedBills = CartageBill::where('status', 'approved')->count();

        // 2. TRENDS
        $billsOverTime = $bills->groupBy(function($b) {
            return date('Y-m-d', strtotime($b->bill_date));
        })->map(function($group) {
            return [
                'count' => $group->count(),
                'amount' => $group->sum('total_amount')
            ];
        });

        // 3. TRANSPORTER PERFORMANCE
        $transporterPerformance = $bills->groupBy(function($b) {
            return $b->transporter->name ?? 'Unknown';
        })->map(function($group, $name) {
            return [
                'name' => $name,
                'amount' => $group->sum('total_amount'),
                'bills' => $group->count()
            ];
        })->values()->sortByDesc('amount')->values();

        // 4. TOP SHIPPING ADDRESSES
        $entries = CartageEntry::with(['shippingAddress', 'customer'])
            ->whereHas('cartageBill', function($q) use ($startDate) {
                $q->where('bill_date', '>=', $startDate);
            })->get();

        $addressDistribution = $entries->groupBy(function($e) {
            return $e->shippingAddress->address_name ?? 'Unknown';
        })->map(function($group, $name) {
            return [
                'name' => $name,
                'amount' => $group->sum('amount'),
                'count' => $group->count()
            ];
        })->values()->sortByDesc('amount')->take(10)->values();

        // 5. VEHICLE UTILIZATION
        $vehicleUsage = $entries->groupBy('vehicle_number')->map(function($group) {
            return [
                'number' => $group->first()->vehicle_number,
                'count' => $group->count(),
                'amount' => $group->sum('amount')
            ];
        })->values()->sortByDesc('count')->take(10)->values();

        return [
            'kpis' => [
                'total_bills' => $totalBillsCount,
                'total_amount' => round($totalBillAmount, 2),
                'pending_bills' => $pendingBills,
                'approved_bills' => $approvedBills,
            ],
            'trends' => [
                'over_time' => $billsOverTime
            ],
            'transporters' => $transporterPerformance,
            'destinations' => $addressDistribution,
            'vehicles' => $vehicleUsage,
            'last_updated' => now()->toISOString()
        ];
    }
}
