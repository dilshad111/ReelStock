<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reel;

class ReelStockReportController extends Controller
{
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
            return [
                'reel_no' => $reel->reel_no,
                'quality' => $reel->paperQuality->quality . ' (' . $reel->paperQuality->gsm_range . ')',
                'reel_size' => $reel->reel_size,
                'supplier' => $reel->supplier->name,
                'original_weight' => $reel->original_weight,
                'consumed_weight' => $reel->original_weight - ($reel->balance_weight ?? $reel->original_weight),
                'balance_weight' => $balanceWeight,
                'rate_per_kg' => $ratePerKg,
                'amount' => $amount,
                'status' => $reel->status,
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
                $history[] = [
                    'date' => $return->return_date,
                    'type' => 'Return',
                    'details' => 'Returned ' . $return->remaining_weight . ' kg',
                    'weight' => $return->remaining_weight, // Positive for returns
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
                ],
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
