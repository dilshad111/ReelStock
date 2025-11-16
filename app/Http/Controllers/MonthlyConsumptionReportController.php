<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReelIssue;
use Illuminate\Support\Facades\DB;

class MonthlyConsumptionReportController extends Controller
{
    public function index(Request $request)
    {
        $query = ReelIssue::select(
            'paper_qualities.quality',
            'paper_qualities.gsm_range',
            'reels.reel_size',
            DB::raw('COUNT(reel_issues.id) as no_of_reels_used'),
            DB::raw('SUM(reel_issues.quantity_issued) as weight_kg_used'),
            DB::raw('SUM(reel_issues.quantity_issued * COALESCE(reel_receipts.rate_per_kg, 0)) as consumption_amount_pkr')
        )
        ->join('reels', 'reel_issues.reel_id', '=', 'reels.id')
        ->join('paper_qualities', 'reels.paper_quality_id', '=', 'paper_qualities.id')
        ->leftJoin('reel_receipts', 'reels.id', '=', 'reel_receipts.reel_id')
        ->groupBy('paper_qualities.quality', 'paper_qualities.gsm_range', 'reels.reel_size');

        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('issue_date', [$request->date_from, $request->date_to]);
        }

        if ($request->has('quality')) {
            $query->where('paper_qualities.quality', 'like', '%' . $request->quality . '%');
        }

        if ($request->has('reel_size')) {
            $query->where('reels.reel_size', $request->reel_size);
        }

        $data = $query->get()->map(function ($item) {
            return [
                'paper_quality_with_gsm' => $item->quality . ' (' . $item->gsm_range . ')',
                'no_of_reels_used' => $item->no_of_reels_used,
                'weight_kg_used' => $item->weight_kg_used,
                'consumption_amount_pkr' => $item->consumption_amount_pkr,
            ];
        });

        return response()->json($data);
    }
}
