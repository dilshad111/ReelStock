<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReelIssue;
use Illuminate\Support\Facades\DB;

class MonthlyConsumptionReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $qualityFilter = $request->input('quality');
        $reelSizeFilter = $request->input('reel_size');

        $issuesQuery = ReelIssue::select(
            'paper_qualities.id as paper_quality_id',
            'paper_qualities.quality',
            'paper_qualities.gsm_range',
            'reels.reel_size',
            DB::raw('COUNT(reel_issues.id) as no_of_reels_used'),
            DB::raw('SUM(reel_issues.quantity_issued) as total_issued_weight'),
            DB::raw('SUM(reel_issues.quantity_issued * COALESCE(reel_receipts.rate_per_kg, 0)) as total_issue_amount')
        )
        ->join('reels', 'reel_issues.reel_id', '=', 'reels.id')
        ->join('paper_qualities', 'reels.paper_quality_id', '=', 'paper_qualities.id')
        ->leftJoin('reel_receipts', 'reels.id', '=', 'reel_receipts.reel_id');

        if ($dateFrom && $dateTo) {
            $issuesQuery->whereBetween('reel_issues.issue_date', [$dateFrom, $dateTo]);
        }

        if ($qualityFilter) {
            $issuesQuery->where('paper_qualities.quality', 'like', '%' . $qualityFilter . '%');
        }

        if ($reelSizeFilter) {
            $issuesQuery->where('reels.reel_size', $reelSizeFilter);
        }

        $issues = $issuesQuery
            ->groupBy('paper_qualities.id', 'paper_qualities.quality', 'paper_qualities.gsm_range', 'reels.reel_size')
            ->get();

        if ($issues->isEmpty()) {
            return response()->json(collect());
        }

        $returnsQuery = DB::table('reel_returns')
            ->select(
                'paper_qualities.id as paper_quality_id',
                'paper_qualities.quality',
                'paper_qualities.gsm_range',
                'reels.reel_size',
                DB::raw('SUM(reel_returns.remaining_weight) as total_return_weight'),
                DB::raw('SUM(reel_returns.remaining_weight * COALESCE(reel_receipts.rate_per_kg, 0)) as total_return_amount')
            )
            ->join('reels', 'reel_returns.reel_id', '=', 'reels.id')
            ->join('paper_qualities', 'reels.paper_quality_id', '=', 'paper_qualities.id')
            ->leftJoin('reel_receipts', 'reels.id', '=', 'reel_receipts.reel_id')
            ->where('reel_returns.returned_to', 'stock');

        if ($dateFrom && $dateTo) {
            $returnsQuery->whereBetween('reel_returns.return_date', [$dateFrom, $dateTo]);
        }

        if ($qualityFilter) {
            $returnsQuery->where('paper_qualities.quality', 'like', '%' . $qualityFilter . '%');
        }

        if ($reelSizeFilter) {
            $returnsQuery->where('reels.reel_size', $reelSizeFilter);
        }

        $returns = $returnsQuery
            ->groupBy('paper_qualities.id', 'paper_qualities.quality', 'paper_qualities.gsm_range', 'reels.reel_size')
            ->get()
            ->mapWithKeys(function ($item) {
                $key = $item->paper_quality_id . '|' . $item->reel_size;
                return [$key => $item];
            });

        $data = $issues->map(function ($item) use ($returns) {
            $key = $item->paper_quality_id . '|' . $item->reel_size;
            $returnData = $returns->get($key);
            $returnWeight = $returnData->total_return_weight ?? 0;
            $returnAmount = $returnData->total_return_amount ?? 0;

            $netWeight = max(($item->total_issued_weight ?? 0) - $returnWeight, 0);
            $netAmount = max(($item->total_issue_amount ?? 0) - $returnAmount, 0);

            return [
                'paper_quality_with_gsm' => $item->quality . ' (' . $item->gsm_range . ')',
                'no_of_reels_used' => (int) $item->no_of_reels_used,
                'weight_kg_used' => $netWeight,
                'consumption_amount_pkr' => $netAmount,
            ];
        });

        return response()->json($data);
    }
}
