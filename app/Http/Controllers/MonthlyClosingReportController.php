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

            // Get reels that existed on or before the target date
            // Include reels if they have at least one receipt on/before target date,
            // OR if they were created in the system on/before target date (fallback)
            $reelsAtDate = DB::table('reels')
                ->where(function ($query) use ($targetDate) {
                    $query->whereDate('reels.created_at', '<=', $targetDate->toDateString())
                        ->orWhereExists(function ($sub) use ($targetDate) {
                            $sub->select(DB::raw(1))
                                ->from('reel_receipts')
                                ->whereColumn('reel_receipts.reel_id', 'reels.id')
                                ->whereNotNull('reel_receipts.receiving_date')
                                ->whereDate('reel_receipts.receiving_date', '<=', $targetDate->toDateString());
                        });
                })
                ->leftJoin('paper_qualities', 'reels.paper_quality_id', '=', 'paper_qualities.id')
                ->leftJoin('suppliers', 'reels.supplier_id', '=', 'suppliers.id')
                ->select(
                    'reels.*',
                    'paper_qualities.quality',
                    'paper_qualities.gsm_range',
                    'suppliers.name as supplier_name'
                )
                ->get();

            // Pre-fetch issues and returns up to target date
            // Include ID and created_at for stable sorting of same-day transactions
            $issuesByReel = DB::table('reel_issues')
                ->where('issue_date', '<=', $targetDate->toDateString())
                ->select('id', 'reel_id', 'issue_date', 'quantity_issued', 'net_consumed_weight', 'return_to_stock_weight', 'created_at')
                ->orderBy('issue_date')
                ->orderBy('id')
                ->get()
                ->groupBy('reel_id');

            $returnsByReel = DB::table('reel_returns')
                ->where('return_date', '<=', $targetDate->toDateString())
                ->select('id', 'reel_id', 'return_date', 'remaining_weight', 'returned_to', 'created_at')
                ->orderBy('return_date')
                ->orderBy('id')
                ->get()
                ->groupBy('reel_id');

            // Calculate closing balances and aggregate by quality and size
            $qualitySizeData = [];

            foreach ($reelsAtDate as $reel) {
                $sizeValue = (float) $reel->reel_size;

                // Calculate closing balance as of target date by re-playing transactions
                $closingBalance = (float) ($reel->original_weight ?? 0);
                
                $reelIssues = $issuesByReel->get($reel->id, collect());
                $reelReturns = $returnsByReel->get($reel->id, collect());
                
                // Combine and sort all transactions
                $transactions = collect();
                foreach ($reelIssues as $issue) {
                    // Use net_consumed_weight if it was recorded (it will be present for newer records)
                    // If it's missing or zero (and there's a return_to_stock_weight), calculate it
                    $consumed = (float)$issue->quantity_issued;
                    if (property_exists($issue, 'net_consumed_weight') && $issue->net_consumed_weight !== null) {
                        // Even if it's 0, it means nothing was consumed from that issue
                        $consumed = (float)$issue->net_consumed_weight;
                    } elseif (property_exists($issue, 'return_to_stock_weight') && (float)$issue->return_to_stock_weight > 0) {
                        $consumed = max(0, (float)$issue->quantity_issued - (float)$issue->return_to_stock_weight);
                    }

                    $transactions->push([
                        'date' => $issue->issue_date,
                        'created_at' => $issue->created_at,
                        'id' => $issue->id,
                        'type' => 'issue',
                        'weight' => $consumed
                    ]);
                }
                foreach ($reelReturns as $return) {
                    if ($return->returned_to === 'supplier') {
                        $transactions->push([
                            'date' => $return->return_date,
                            'created_at' => $return->created_at,
                            'id' => $return->id,
                            'type' => 'return_supplier',
                            'weight' => (float)$return->remaining_weight
                        ]);
                    }
                }
                
                // Sort by date, then by creation time/ID for same-day transactions
                $sortedTransactions = $transactions->sort(function($a, $b) {
                    if ($a['date'] !== $b['date']) {
                        return strcmp($a['date'], $b['date']);
                    }
                    if (isset($a['created_at']) && isset($b['created_at']) && $a['created_at'] !== $b['created_at']) {
                        return strcmp($a['created_at'], $b['created_at']);
                    }
                    return $a['id'] <=> $b['id'];
                });
                
                foreach ($sortedTransactions as $tx) {
                    if ($tx['type'] === 'issue') {
                        $closingBalance -= $tx['weight'];
                    } elseif ($tx['type'] === 'return_supplier') {
                        // Return to supplier removes that weight from stock
                        $closingBalance -= $tx['weight'];
                    }
                }

                $closingBalance = max(0, $closingBalance);

                // For current date, we can also cross-verify with live balance_weight if needed,
                // but for historical dates we must rely on the calculation.
                // If it's today, the live balance_weight is usually the most reliable.
                if ($targetDate->isToday()) {
                    // However, we still use the calculation to maintain consistency with historical reports.
                    // If there's a big gap, it might indicate missing transaction records.
                }

                // Normalize size key (e.g., 20 -> 20")
                $normalizedSize = rtrim(rtrim(number_format($sizeValue, 2, '.', ''), '0'), '.');
                $sizeKey = $normalizedSize . '"';

                // Only include reels with positive balance at target date
                if ($closingBalance > 0.01) { // Use small threshold for floats
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

            // Get all unique sizes that have weights in the qualitySizeData
            $allSizesSet = [];
            foreach ($qualitySizeData as $quality => $sizes) {
                foreach ($sizes as $sizeKey => $weight) {
                    $allSizesSet[$sizeKey] = true;
                }
            }
            $allSizes = array_keys($allSizesSet);

            // Sort sizes numerically
            usort($allSizes, function($a, $b) {
                $numA = (float) rtrim($a, '"');
                $numB = (float) rtrim($b, '"');
                return $numA <=> $numB;
            });

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
            if (!empty($pivotData)) {
                $totalsRow = ['quality' => 'TOTAL'];
                foreach ($allSizes as $size) {
                    $totalsRow[$size] = $totalBySize[$size] ?? 0;
                }
                $totalsRow['total'] = $grandTotal;
                $pivotData[] = $totalsRow;
            }

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
