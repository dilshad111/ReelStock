<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReelIssue;
use App\Models\Reel;
use App\Models\PaperQuality;
use Illuminate\Support\Facades\DB;

class ReelUsageReportController extends Controller
{
    public function usageIntelligence()
    {
        $allIssues = ReelIssue::with(['reel.paperQuality'])->get();

        if ($allIssues->isEmpty()) {
            return response()->json([
                'most_used_quality' => [],
                'most_used_size_gsm' => [],
                'avg_consumption' => 0,
                'total_reels_consumed' => 0,
                'monthly_trends' => [],
                'quarterly_trends' => []
            ]);
        }

        // Most used reel quality
        $mostUsedQuality = $allIssues->groupBy(function($issue) {
            return $issue->reel->paperQuality->quality ?? 'Unknown';
        })->map(function($group) {
            return $group->sum('quantity_issued');
        })->sortByDesc(function($v) { return $v; })->take(10);

        // Most used size/GSM combination
        $mostUsedSizeGsm = $allIssues->groupBy(function($issue) {
            $size = $issue->reel->reel_size;
            $gsm = $issue->reel->paperQuality->gsm_range ?? 'N/A';
            return $size . '" / ' . $gsm . ' GSM';
        })->map(function($group) {
            return $group->sum('quantity_issued');
        })->sortByDesc(function($v) { return $v; })->take(10);

        // Average weight consumption per reel
        $avgConsumption = $allIssues->avg('quantity_issued');

        // Total number of reels consumed
        $totalReelsConsumed = $allIssues->unique('reel_id')->count();

        // Monthly trends
        $monthlyTrends = $allIssues->groupBy(function($issue) {
            return date('Y-m', strtotime($issue->issue_date));
        })->map(function($group) {
            return [
                'weight' => $group->sum('quantity_issued'),
                'count' => $group->unique('reel_id')->count()
            ];
        })->sortKeys();

        // Quarterly trends
        $quarterlyTrends = $allIssues->groupBy(function($issue) {
            $month = date('n', strtotime($issue->issue_date));
            $year = date('Y', strtotime($issue->issue_date));
            $quarter = ceil($month / 3);
            return $year . ' Q' . $quarter;
        })->map(function($group) {
            return [
                'weight' => $group->sum('quantity_issued'),
                'count' => $group->unique('reel_id')->count()
            ];
        })->sortKeys();

        return response()->json([
            'most_used_quality' => $mostUsedQuality,
            'most_used_size_gsm' => $mostUsedSizeGsm,
            'avg_consumption' => round($avgConsumption, 2),
            'total_reels_consumed' => $totalReelsConsumed,
            'monthly_trends' => $monthlyTrends,
            'quarterly_trends' => $quarterlyTrends
        ]);
    }

    public function predictiveAnalytics(Request $request)
    {
        $qualityId = $request->get('paper_quality_id');
        $reelSize = $request->get('reel_size');

        $query = ReelIssue::query()->with(['reel.paperQuality']);

        if ($qualityId) {
            $query->whereHas('reel', function($q) use ($qualityId) {
                $q->where('paper_quality_id', $qualityId);
            });
        }

        if ($reelSize) {
            $query->whereHas('reel', function($q) use ($reelSize) {
                $q->where('reel_size', $reelSize);
            });
        }

        $issues = $query->select(
            DB::raw("DATE_FORMAT(issue_date, '%Y-%m') as month"),
            DB::raw("SUM(quantity_issued) as total_weight")
        )
        ->groupBy('month')
        ->orderBy('month', 'asc')
        ->get();

        if ($issues->count() < 2) {
            return response()->json([
                'actual' => $issues,
                'forecast' => [],
                'message' => 'Insufficient data for prediction. At least 2 months of history required.'
            ]);
        }

        $weights = $issues->pluck('total_weight')->toArray();
        $n = count($weights);
        $lastMonthStr = $issues->last()->month;

        // Simple Linear Regression for Trend
        $x_sum = 0; $y_sum = 0; $xy_sum = 0; $xx_sum = 0;
        foreach($weights as $i => $y) {
            $x_sum += $i;
            $y_sum += $y;
            $xy_sum += ($i * $y);
            $xx_sum += ($i * $i);
        }
        
        $denominator = ($n * $xx_sum - $x_sum * $x_sum);
        if ($denominator == 0) $slope = 0;
        else $slope = ($n * $xy_sum - $x_sum * $y_sum) / $denominator;
        
        $intercept = ($y_sum - $slope * $x_sum) / $n;

        $forecast = [];
        for ($i = $n; $i < $n + 6; $i++) {
            $nextMonth = date('Y-m', strtotime($lastMonthStr . " -01 +" . ($i - $n + 1) . " month"));
            $prediction = $slope * $i + $intercept;
            $forecast[] = [
                'month' => $nextMonth,
                'predicted_weight' => round(max(0, $prediction), 2)
            ];
        }

        return response()->json([
            'actual' => $issues,
            'forecast' => $forecast,
            'slope' => $slope,
            'intercept' => $intercept
        ]);
    }
}
