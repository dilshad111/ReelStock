<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Reel;

class MonthlyClosingReportController extends Controller
{
    public function index(Request $request)
    {
        try {
            $date = $request->get('date', date('Y-m-d'));

            // Return data for a specific date
            $targetDate = Carbon::parse($date);

            // Get reels that have been received on or before the target date
            $reelsAtDate = DB::table('reels')
                ->join('reel_receipts', 'reels.id', '=', 'reel_receipts.reel_id')
                ->leftJoin('paper_qualities', 'reels.paper_quality_id', '=', 'paper_qualities.id')
                ->leftJoin('suppliers', 'reels.supplier_id', '=', 'suppliers.id')
                ->whereNotNull('reel_receipts.receiving_date')
                ->whereDate('reel_receipts.receiving_date', '<=', $targetDate->toDateString())
                ->select(
                    'reels.*',
                    'reel_receipts.receiving_date',
                    'paper_qualities.quality',
                    'paper_qualities.gsm_range',
                    'suppliers.name as supplier_name'
                )
                ->get();

            // Pre-fetch issues and returns up to target date
            $issuesByReel = DB::table('reel_issues')
                ->where('issue_date', '<=', $targetDate->toDateString())
                ->select('reel_id', 'issue_date', 'quantity_issued')
                ->orderBy('issue_date')
                ->get()
                ->groupBy('reel_id');

            $returnsByReel = DB::table('reel_returns')
                ->where('return_date', '<=', $targetDate->toDateString())
                ->select('reel_id', 'return_date', 'remaining_weight', 'returned_to')
                ->orderBy('return_date')
                ->get()
                ->groupBy('reel_id');

            // Calculate closing balances and aggregate by quality and size
            $qualitySizeData = [];

            foreach ($reelsAtDate as $reel) {
                $sizeValue = (float) $reel->reel_size;

                // Only include reels within the defined size range
                if ($sizeValue < 20 || $sizeValue > 55) {
                    continue;
                }

                // Calculate closing balance as of target date
                $issuesForReel = $issuesByReel->get($reel->id, collect());
                $closingBalance = $reel->original_weight ?? 0;

                if ($returnsByReel->has($reel->id)) {
                    $returns = $returnsByReel->get($reel->id);
                    $latestReturn = $returns->last();
                    
                    // If the latest return was to supplier, the reel is no longer in stock
                    if ($latestReturn->returned_to === 'supplier') {
                        $closingBalance = 0;
                    } else {
                        // Latest return was to stock
                        $closingBalance = $latestReturn->remaining_weight ?? 0;

                        $issuesAfterReturn = $issuesForReel->filter(function ($issue) use ($latestReturn) {
                            return Carbon::parse($issue->issue_date)->greaterThan(Carbon::parse($latestReturn->return_date));
                        });

                        $closingBalance -= $issuesAfterReturn->sum('quantity_issued');
                    }
                } else {
                    $closingBalance -= $issuesForReel->sum('quantity_issued');
                }

                $closingBalance = max(0, $closingBalance);

                // Normalize size key (e.g., 20 -> 20")
                $normalizedSize = rtrim(rtrim(number_format($sizeValue, 2, '.', ''), '0'), '.');
                $sizeKey = $normalizedSize . '"';

                // Only include reels with positive balance at target date
                if ($closingBalance > 0) {
                    $qualityKey = ($reel->quality ?? 'Unknown') . ' (' . ($reel->gsm_range ?? 'Unknown') . ')';

                    if (!isset($qualitySizeData[$qualityKey])) {
                        $qualitySizeData[$qualityKey] = [];
                    }

                    if (!isset($qualitySizeData[$qualityKey][$sizeKey])) {
                        $qualitySizeData[$qualityKey][$sizeKey] = 0;
                    }

                    $qualitySizeData[$qualityKey][$sizeKey] += $closingBalance;
                }
            }

            // Define reel size columns from 20" through 55"
            $allSizes = [];
            for ($size = 20; $size <= 55; $size++) {
                $allSizes[] = $size . '"';
            }

            // Format data for frontend
            $pivotData = [];
            $totalBySize = array_fill_keys($allSizes, 0);
            $grandTotal = 0;

            foreach ($qualitySizeData as $quality => $sizes) {
                $rowData = ['quality' => $quality];
                $rowTotal = 0;

                foreach ($allSizes as $size) {
                    $weight = $sizes[$size] ?? 0;
                    $rowData[$size] = $weight;
                    $rowTotal += $weight;
                    $totalBySize[$size] = ($totalBySize[$size] ?? 0) + $weight;
                }

                $rowData['total'] = $rowTotal;
                $grandTotal += $rowTotal;
                $pivotData[] = $rowData;
            }

            // Add totals row
            $totalsRow = ['quality' => 'TOTAL'];
            foreach ($allSizes as $size) {
                $totalsRow[$size] = $totalBySize[$size] ?? 0;
            }
            $totalsRow['total'] = $grandTotal;
            $pivotData[] = $totalsRow;

            return response()->json([
                'date' => $date,
                'pivot_data' => $pivotData,
                'reel_sizes' => $allSizes
            ]);
        } catch (\Exception $e) {
            \Log::error('Reel balance report error: ' . $e->getMessage());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
