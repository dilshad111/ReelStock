<?php

namespace App\Domains\Production\Actions;

use App\Models\JobCard;
use App\Models\ProductionLog;
use App\Models\RMItem;
use App\Models\JobCardItem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GetProductionDashboardDataAction
{
    public function execute()
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // 1. KPIs
        $kpis = [
            'active_job_cards' => JobCard::whereIn('status', ['Open', 'In-Progress'])->count(),
            'total_planned_qty' => (float)JobCard::whereIn('status', ['Open', 'In-Progress'])->sum('planned_qty'),
            'produced_this_month' => (float)ProductionLog::where('date', '>=', $startOfMonth->toDateString())
                ->whereHas('step', function($q) {
                    $q->where('step_name', 'Packing'); // Final step as indicator of finished output
                })
                ->sum('quantity'),
            'avg_wastage' => (float)ProductionLog::where('date', '>=', $startOfMonth->toDateString())->avg('wastage') ?? 0,
        ];

        // 2. Status Distribution
        $statusDistribution = JobCard::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // 3. Daily Production Trend (Last 30 days)
        $dailyTrend = ProductionLog::select(
                DB::raw('date'),
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(wastage) as total_wastage')
            )
            ->where('date', '>', $now->copy()->subDays(30)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 4. Step-wise Output (Monthly)
        $stepOutput = ProductionLog::join('job_card_steps', 'production_logs.job_card_step_id', '=', 'job_card_steps.id')
            ->select('job_card_steps.step_name', DB::raw('SUM(production_logs.quantity) as total_qty'))
            ->where('production_logs.date', '>=', $startOfMonth->toDateString())
            ->groupBy('job_card_steps.step_name')
            ->get();

        // 5. Material Shortage (Active Job Cards where required > available)
        // This is a bit complex, let's get top 5 active job cards with lowest material coverage
        $activeJobCardIds = JobCard::whereIn('status', ['Open', 'In-Progress'])->pluck('id');
        
        $materialStatus = DB::table('job_card_items')
            ->join('rm_items', 'job_card_items.rm_item_id', '=', 'rm_items.id')
            ->join('job_cards', 'job_card_items.job_card_id', '=', 'job_cards.id')
            ->select(
                'job_cards.job_card_no',
                'rm_items.name as material_name',
                'job_card_items.required_qty',
                'job_card_items.consumed_qty',
                'rm_items.balance as available_stock'
            )
            ->whereIn('job_card_items.job_card_id', $activeJobCardIds)
            ->whereRaw('(job_card_items.required_qty - job_card_items.consumed_qty) > rm_items.balance')
            ->limit(10)
            ->get();

        return [
            'kpis' => $kpis,
            'status_distribution' => $statusDistribution,
            'daily_trend' => $dailyTrend,
            'step_output' => $stepOutput,
            'material_shortage' => $materialStatus
        ];
    }
}
