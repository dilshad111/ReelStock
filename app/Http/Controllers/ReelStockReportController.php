<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;

class ReelStockReportController extends Controller
{
    public function getAvailableSizes(Request $request)
    {
        $query = Reel::query();

        if ($request->has('supplier') && $request->supplier !== '') {
            $query->where('supplier_id', $request->supplier);
        }

        if ($request->has('quality') && $request->quality !== '') {
            $query->where('paper_quality_id', $request->quality);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        } else {
            // Default: Only show sizes for reels currently in stock
            $query->where(function ($q) {
                $q->where('balance_weight', '>', 0)
                  ->orWhere(function ($inner) {
                      $inner->whereNull('balance_weight')
                            ->where('original_weight', '>', 0);
                  });
            });
        }

        $sizes = $query->distinct()->pluck('reel_size')->filter()->values();

        // Sort numerically
        $sortedSizes = $sizes->sort(function($a, $b) {
            return (float)$a <=> (float)$b;
        })->values();

        return response()->json($sortedSizes);
    }

    public function getAvailableQualities(Request $request)
    {
        $query = Reel::query();

        if ($request->has('supplier') && $request->supplier !== '') {
            $query->where('supplier_id', $request->supplier);
        }

        if ($request->has('size') && $request->size !== '') {
            $query->where('reel_size', $request->size);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        } else {
            // Default: Only show qualities for reels currently in stock
            $query->where(function ($q) {
                $q->where('balance_weight', '>', 0)
                  ->orWhere(function ($inner) {
                      $inner->whereNull('balance_weight')
                            ->where('original_weight', '>', 0);
                  });
            });
        }

        $qualityIds = $query->distinct()->pluck('paper_quality_id')->filter()->values();
        
        $qualities = \App\Models\PaperQuality::whereIn('id', $qualityIds)->get();

        return response()->json($qualities);
    }

    public function getAvailableSuppliers(Request $request)
    {
        $query = Reel::query();

        if ($request->has('quality') && $request->quality !== '') {
            $query->where('paper_quality_id', $request->quality);
        }

        if ($request->has('size') && $request->size !== '') {
            $query->where('reel_size', $request->size);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        } else {
            // Default: Only show suppliers for reels currently in stock
            $query->where(function ($q) {
                $q->where('balance_weight', '>', 0)
                  ->orWhere(function ($inner) {
                      $inner->whereNull('balance_weight')
                            ->where('original_weight', '>', 0);
                  });
            });
        }

        $supplierIds = $query->distinct()->pluck('supplier_id')->filter()->values();
        
        $suppliers = \App\Models\Supplier::whereIn('id', $supplierIds)->get();

        return response()->json($suppliers);
    }

    public function index(Request $request)
    {
        $query = Reel::with(['paperQuality', 'supplier', 'receipts' => function ($q) {
            $q->orderByDesc('receiving_date');
        }]);

        if ($request->has('quality') && $request->quality !== '') {
            $query->whereHas('paperQuality', function ($q) use ($request) {
                $q->where('id', $request->quality);
            });
        }

        if ($request->has('size') && $request->size !== '') {
            $query->where('reel_size', $request->size);
        }

        if ($request->has('supplier') && $request->supplier !== '') {
            $query->where('supplier_id', $request->supplier);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        } else {
            // Default: Only show reels currently in stock (in_stock or partially_used)
            // or those with balance_weight > 0 if status is not explicitly "fully_used"
            $query->where(function ($q) {
                $q->where('balance_weight', '>', 0)
                  ->orWhere(function ($inner) {
                      $inner->whereNull('balance_weight')
                            ->where('original_weight', '>', 0);
                  });
            });
        }

        if ($request->filled('balance_min')) {
            $query->where(function ($q) use ($request) {
                $q->where('balance_weight', '>=', $request->balance_min)
                  ->orWhere(function ($inner) use ($request) {
                      $inner->whereNull('balance_weight')
                            ->where('original_weight', '>=', $request->balance_min);
                  });
            });
        }

        if ($request->filled('balance_max')) {
            $query->where(function ($q) use ($request) {
                $q->where('balance_weight', '<=', $request->balance_max)
                  ->orWhere(function ($inner) use ($request) {
                      $inner->whereNull('balance_weight')
                            ->where('original_weight', '<=', $request->balance_max);
                  });
            });
        }

        $data = $query->get()->map(function ($reel) {
            $latestReceipt = $reel->receipts->first();
            $ratePerKg = $latestReceipt->rate_per_kg ?? 0;
            $balanceWeight = $reel->balance_weight ?? $reel->original_weight;
            $amount = $balanceWeight * $ratePerKg;
            
            // Format status for display
            $displayStatus = str_replace('_', ' ', ucfirst($reel->status));
            if ($reel->status === 'in_stock') $displayStatus = 'In Stock';
            if ($reel->status === 'partially_used') $displayStatus = 'Partially Used';
            if ($reel->status === 'fully_used') $displayStatus = 'Fully Used';

            return [
                'reel_no' => $reel->reel_no,
                'quality' => $reel->paperQuality ? ($reel->paperQuality->quality . ' (' . $reel->paperQuality->gsm_range . ')') : 'N/A',
                'reel_size' => $reel->reel_size,
                'supplier' => $reel->supplier ? $reel->supplier->name : 'N/A',
                'original_weight' => $reel->original_weight,
                'consumed_weight' => $reel->original_weight - ($reel->balance_weight ?? $reel->original_weight),
                'balance_weight' => $balanceWeight,
                'rate_per_kg' => $ratePerKg,
                'amount' => $amount,
                'status' => $displayStatus,
                'raw_status' => $reel->status,
            ];
        });

        return response()->json($data);
    }

    public function getReelHistory($reel_no)
    {
        try {
            $reel = Reel::with(['paperQuality', 'supplier', 'receipts' => function ($q) {
                $q->orderByDesc('receiving_date');
            }, 'issues', 'returns'])->where('reel_no', $reel_no)->first();

            if (!$reel) {
                return response()->json(['message' => 'Reel not found'], 404);
            }

            $latestReceipt = $reel->receipts->first();
            $history = [];

            // Receipt history
            foreach ($reel->receipts as $receipt) {
                $history[] = [
                    'date' => $receipt->receiving_date,
                    'type' => 'Receipt',
                    'details' => 'Received ' . $reel->original_weight . ' kg',
                    'weight' => $reel->original_weight,
                ];
            }

            // Issue history
            foreach ($reel->issues as $issue) {
                $history[] = [
                    'date' => $issue->issue_date,
                    'type' => 'Issue',
                    'details' => 'Issued ' . $issue->quantity_issued . ' kg',
                    'weight' => -$issue->quantity_issued, // Negative for issues
                ];
            }

            // Return history
            foreach ($reel->returns as $return) {
                $isSupplierReturn = $return->returned_to === 'supplier';
                $weight = $isSupplierReturn ? -$return->remaining_weight : $return->remaining_weight;
                $displayType = $isSupplierReturn ? 'Return to Supplier' : 'Return';
                $details = $isSupplierReturn 
                    ? 'Returned to Supplier ' . $return->remaining_weight . ' kg' 
                    : 'Returned ' . $return->remaining_weight . ' kg';

                if (!$isSupplierReturn && $return->return_location) {
                    $details .= " (Location: {$return->return_location})";
                }

                $history[] = [
                    'date' => $return->return_date,
                    'type' => $displayType,
                    'details' => $details,
                    'weight' => $weight, // Negative for supplier returns, positive for stock returns
                ];
            }

            // Sort by date
            usort($history, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            // Calculate running balance
            $balance = 0;
            foreach ($history as &$item) {
                $balance += $item['weight'];
                $item['balance'] = $balance;
            }

            return response()->json([
                'reel' => [
                    'reel_no' => $reel->reel_no,
                    'quality' => $reel->paperQuality->quality . ' (' . $reel->paperQuality->gsm_range . ')',
                    'supplier' => $reel->supplier->name,
                    'original_weight' => $reel->original_weight,
                    'current_balance' => $reel->balance_weight,
                    'gsm' => $latestReceipt ? $latestReceipt->gsm : null,
                    'bursting_strength' => $latestReceipt ? $latestReceipt->bursting_strength : null,
                    'qc_status' => $latestReceipt ? $latestReceipt->qc_status : null,
                ],
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getReelStockCount(Request $request)
    {
        $query = Reel::with(['paperQuality']);

        if ($request->has('quality') && $request->quality !== '') {
            $query->where('paper_quality_id', $request->quality);
        }

        if ($request->has('size') && $request->size !== '') {
            $query->where('reel_size', $request->size);
        }

        // Only show reels currently in stock
        $query->where(function ($q) {
            $q->where('balance_weight', '>', 0)
              ->orWhere(function ($inner) {
                  $inner->whereNull('balance_weight')
                        ->where('original_weight', '>', 0);
              });
        });

        $reels = $query->get();

        $grouped = $reels->groupBy(function($reel) {
            return $reel->paper_quality_id . '_' . $reel->reel_size;
        })->map(function($group) {
            $first = $group->first();
            return [
                'quality_id' => $first->paper_quality_id,
                'quality_name' => $first->paperQuality->quality . ' (' . $first->paperQuality->gsm_range . ')',
                'reel_size' => (float)$first->reel_size,
                'no_of_reels' => $group->count(),
                'total_balance_weight' => (float)$group->sum(function($reel) {
                    return $reel->balance_weight ?? $reel->original_weight;
                }),
            ];
        })->values();

        // Sort by quality name then by size
        $sorted = $grouped->sortBy([
            ['quality_name', 'asc'],
            ['reel_size', 'asc'],
        ])->values();

        return response()->json($sorted);
    }
}
